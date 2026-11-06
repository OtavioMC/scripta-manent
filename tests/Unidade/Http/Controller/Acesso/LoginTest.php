<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http\Controller\Acesso;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Http\Acesso\Credencial;
use ScriptaManent\Http\Acesso\ExigeLogin;
use ScriptaManent\Http\Acesso\LimiteDeTentativas;
use ScriptaManent\Http\Acesso\SessaoDeAcesso;
use ScriptaManent\Http\Acesso\Situacao;
use ScriptaManent\Http\Controller\Acesso\EntrarController;
use ScriptaManent\Http\Controller\Acesso\SairController;
use ScriptaManent\Http\Controller\Acesso\TelaDeLoginController;
use ScriptaManent\Http\Visao;
use ScriptaManent\Tests\Duble\LogEmMemoria;
use ScriptaManent\Tests\Duble\SessaoEmMemoria;
use ScriptaManent\Tests\Duble\TwigDeTeste;

final class LoginTest extends TestCase
{
    private SessaoEmMemoria $sessao;
    private SessaoDeAcesso $acesso;
    private LogEmMemoria $log;
    private Visao $visao;
    private string $pasta;

    protected function setUp(): void
    {
        $this->sessao = new SessaoEmMemoria();
        $this->acesso = new SessaoDeAcesso($this->sessao, static fn (): int => 1_000_000);
        $this->log = new LogEmMemoria();
        $this->visao = new Visao(TwigDeTeste::criar(), new Psr17Factory());
        $this->pasta = sys_get_temp_dir() . '/login-teste-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->pasta . '/*.json') ?: [] as $arquivo) {
            unlink($arquivo);
        }
        if (is_dir($this->pasta)) {
            rmdir($this->pasta);
        }
    }

    public function testSemLoginATelaPedidaVaiParaOLoginEGuardaODestino(): void
    {
        $resposta = $this->protegida(new ServerRequest('GET', '/livros?busca=gaiman', ['Accept' => 'text/html']));

        self::assertSame(303, $resposta->getStatusCode());
        self::assertSame('/login', $resposta->getHeaderLine('Location'));
        self::assertSame('/livros?busca=gaiman', $this->sessao->obter('destino'));
    }

    public function testPedidoQueNaoEPaginaNaoViraDestinoDoLogin(): void
    {
        $this->protegida(new ServerRequest('GET', '/favicon.ico', ['Accept' => 'image/avif,image/webp,*/*']));

        self::assertNull($this->sessao->obter('destino'));
    }

    public function testLoginEVersaoSaoPublicos(): void
    {
        self::assertSame(200, $this->protegida(new ServerRequest('GET', '/login'))->getStatusCode());
        self::assertSame(200, $this->protegida(new ServerRequest('GET', '/versao'))->getStatusCode());
    }

    public function testTelaDeLoginTemOFormularioComToken(): void
    {
        $pagina = (string) (new TelaDeLoginController($this->visao, $this->acesso, $this->sessao))
            ->handle(new ServerRequest('GET', '/login'))->getBody();

        self::assertStringContainsString('action="/login"', $pagina);
        self::assertStringContainsString('name="_csrf"', $pagina);
        self::assertStringContainsString('autocomplete="current-password"', $pagina);
    }

    public function testLoginCertoAutenticaEVoltaParaODestino(): void
    {
        $this->sessao->guardar('destino', '/livros?busca=gaiman');

        $resposta = $this->entrar('avaliador', 'senha-certa');

        self::assertSame(303, $resposta->getStatusCode());
        self::assertSame('/livros?busca=gaiman', $resposta->getHeaderLine('Location'));
        self::assertSame(Situacao::Autenticado, $this->acesso->situacao());
        self::assertSame('Login realizado', $this->log->registros[0]['mensagem'] ?? null);
    }

    public function testDestinoParaOutroSiteEIgnorado(): void
    {
        $this->sessao->guardar('destino', '//exemplo.com/roubo');

        self::assertSame('/', $this->entrar('avaliador', 'senha-certa')->getHeaderLine('Location'));
    }

    public function testUsuarioInexistenteESenhaErradaTemAMesmaResposta(): void
    {
        $senhaErrada = $this->entrar('avaliador', 'errada');
        $usuarioErrado = $this->entrar('fulano', 'senha-certa');

        self::assertSame(401, $senhaErrada->getStatusCode());
        self::assertSame(401, $usuarioErrado->getStatusCode());
        self::assertStringContainsString('Usuário ou senha incorretos.', (string) $senhaErrada->getBody());
        self::assertStringContainsString('Usuário ou senha incorretos.', (string) $usuarioErrado->getBody());
        self::assertSame(Situacao::Anonimo, $this->acesso->situacao());
    }

    public function testDepoisDeCincoFalhasOLoginFicaBloqueado(): void
    {
        for ($i = 0; $i < LimiteDeTentativas::FALHAS_LIVRES; $i++) {
            $this->entrar('avaliador', 'errada');
        }

        $resposta = $this->entrar('avaliador', 'senha-certa');

        self::assertSame(429, $resposta->getStatusCode());
        self::assertStringContainsString('Muitas tentativas seguidas.', (string) $resposta->getBody());
        self::assertSame(Situacao::Anonimo, $this->acesso->situacao());
    }

    public function testSairEncerraASessaoEVoltaParaOLogin(): void
    {
        $this->acesso->entrar('avaliador');

        $resposta = (new SairController($this->visao, $this->acesso, $this->log))
            ->handle(new ServerRequest('POST', '/sair'));

        self::assertSame('/login?saiu=1', $resposta->getHeaderLine('Location'));
        self::assertSame(Situacao::Anonimo, $this->acesso->situacao());
    }

    private function entrar(string $usuario, string $senha): ResponseInterface
    {
        $controller = new EntrarController(
            $this->visao,
            Credencial::doFormatoHtpasswd('avaliador:' . password_hash('senha-certa', PASSWORD_BCRYPT, ['cost' => 4])),
            $this->acesso,
            $this->sessao,
            new LimiteDeTentativas($this->pasta, static fn (): int => 1_000_000),
            $this->log,
        );

        return $controller->handle(
            (new ServerRequest('POST', '/login', [], null, '1.1', ['REMOTE_ADDR' => '203.0.113.7']))
                ->withParsedBody(['usuario' => $usuario, 'senha' => $senha]),
        );
    }

    private function protegida(ServerRequestInterface $requisicao): ResponseInterface
    {
        $aplicacao = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };

        return (new ExigeLogin($aplicacao, $this->acesso, $this->sessao, $this->visao))->handle($requisicao);
    }
}
