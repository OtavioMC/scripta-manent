<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http\Controller\Autor;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ScriptaManent\Application\Autor\CadastroDeAutores;
use ScriptaManent\Domain\Autor\Autor;
use ScriptaManent\Http\Controller\Autor\ExcluirAutorController;
use ScriptaManent\Http\Controller\Autor\FormularioDeAutorController;
use ScriptaManent\Http\Controller\Autor\ListaDeAutoresController;
use ScriptaManent\Http\Controller\Autor\SalvarAutorController;
use ScriptaManent\Http\Controller\Autor\TelaDeFormularioDeAutor;
use ScriptaManent\Http\PaginaDeErro;
use ScriptaManent\Http\Visao;
use ScriptaManent\Tests\Duble\RepositorioDeAutoresEmMemoria;
use ScriptaManent\Tests\Duble\SessaoEmMemoria;
use Twig\Environment;

final class TelasDeAutoresTest extends TestCase
{
    private RepositorioDeAutoresEmMemoria $repositorio;
    private SessaoEmMemoria $sessao;
    private Visao $visao;
    private CadastroDeAutores $cadastro;
    private TelaDeFormularioDeAutor $formulario;

    protected function setUp(): void
    {
        $twig = require __DIR__ . '/../../../../../config/twig.php';
        self::assertInstanceOf(Environment::class, $twig);

        $this->repositorio = new RepositorioDeAutoresEmMemoria();
        $this->sessao = new SessaoEmMemoria();
        $this->visao = new Visao($twig, new Psr17Factory());
        $this->cadastro = new CadastroDeAutores($this->repositorio);
        $this->formulario = new TelaDeFormularioDeAutor($this->visao, new PaginaDeErro($this->visao));
    }

    public function testListaMostraOsAutoresComLinksDeEdicao(): void
    {
        $autor = $this->repositorio->inserir(Autor::novo('Clarice Lispector'));

        $pagina = (string) $this->lista()->getBody();

        self::assertStringContainsString('Clarice Lispector', $pagina);
        self::assertStringContainsString(sprintf('href="/autores/%d/editar"', (int) $autor->codigo), $pagina);
    }

    public function testListaTrazOFormularioEmModalEOsDadosParaEditar(): void
    {
        $autor = $this->repositorio->inserir(Autor::novo('Clarice Lispector'));

        $pagina = (string) $this->lista()->getBody();

        self::assertStringContainsString('id="modal-formulario"', $pagina);
        self::assertMatchesRegularExpression(
            sprintf('#<input [^>]*name="nome"[^>]*maxlength="%d"#', Autor::NOME_MAXIMO),
            $pagina,
        );
        self::assertStringContainsString(sprintf('data-acao="/autores/%d"', (int) $autor->codigo), $pagina);
        self::assertStringContainsString('data-campos="{&quot;nome&quot;:&quot;Clarice Lispector&quot;}"', $pagina);
    }

    public function testListaVaziaDizComoComecar(): void
    {
        $pagina = (string) $this->lista()->getBody();

        self::assertStringContainsString('Nenhum autor cadastrado.', $pagina);
        self::assertStringContainsString('href="/autores/novo"', $pagina);
    }

    public function testAvisoDaSessaoApareceUmaVezSo(): void
    {
        $this->sessao->guardar('aviso', 'Autor Clarice Lispector cadastrado.');

        self::assertStringContainsString('Autor Clarice Lispector cadastrado.', (string) $this->lista()->getBody());
        self::assertStringNotContainsString('Autor Clarice Lispector cadastrado.', (string) $this->lista()->getBody());
    }

    public function testFormularioUsaOLimiteDoDominioNoCampoNome(): void
    {
        $pagina = (string) $this->abrirFormulario()->getBody();

        self::assertMatchesRegularExpression(
            sprintf('#<input [^>]*name="nome"[^>]*maxlength="%d" required#', Autor::NOME_MAXIMO),
            $pagina,
        );
        self::assertStringContainsString(Autor::NOME_OBRIGATORIO, $pagina);
    }

    public function testFormularioDeEdicaoTrazONomeAtual(): void
    {
        $autor = $this->repositorio->inserir(Autor::novo('Clarice Lispector'));

        $pagina = (string) $this->abrirFormulario((int) $autor->codigo)->getBody();

        self::assertStringContainsString('value="Clarice Lispector"', $pagina);
        self::assertStringContainsString(sprintf('action="/autores/%d"', (int) $autor->codigo), $pagina);
    }

    public function testEdicaoDeAutorInexistenteRespondeNaoEncontrado(): void
    {
        $resposta = $this->abrirFormulario(999);

        self::assertSame(404, $resposta->getStatusCode());
        self::assertStringContainsString('Nenhum autor cadastrado tem o código 999.', (string) $resposta->getBody());
    }

    public function testCadastraERedirecionaParaAListaComAviso(): void
    {
        $resposta = $this->salvar(null, 'Rubem Fonseca');

        self::assertSame(303, $resposta->getStatusCode());
        self::assertSame('/autores', $resposta->getHeaderLine('Location'));
        self::assertSame('Rubem Fonseca', $this->repositorio->listar()[0]->nome ?? null);
        self::assertSame('Autor Rubem Fonseca cadastrado.', $this->sessao->obter('aviso'));
    }

    public function testNomeInvalidoReabreOFormularioComAMensagemEOValorDigitado(): void
    {
        $nome = str_repeat('a', Autor::NOME_MAXIMO + 1);

        $resposta = $this->salvar(null, $nome);

        self::assertSame(422, $resposta->getStatusCode());
        self::assertStringContainsString('Nome aceita até 40 caracteres.', (string) $resposta->getBody());
        self::assertStringContainsString(sprintf('value="%s"', $nome), (string) $resposta->getBody());
        self::assertSame([], $this->repositorio->listar());
    }

    public function testAlteraOAutorExistente(): void
    {
        $autor = $this->repositorio->inserir(Autor::novo('Rubem Fonsec'));

        $resposta = $this->salvar((int) $autor->codigo, 'Rubem Fonseca');

        self::assertSame(303, $resposta->getStatusCode());
        self::assertSame('Rubem Fonseca', $this->repositorio->buscar((int) $autor->codigo)?->nome);
        self::assertSame('Autor Rubem Fonseca alterado.', $this->sessao->obter('aviso'));
    }

    public function testAlterarAutorInexistenteRespondeNaoEncontrado(): void
    {
        self::assertSame(404, $this->salvar(999, 'Rubem Fonseca')->getStatusCode());
    }

    public function testExcluiERedirecionaComAviso(): void
    {
        $autor = $this->repositorio->inserir(Autor::novo('Rubem Fonseca'));

        $resposta = $this->excluir((int) $autor->codigo);

        self::assertSame(303, $resposta->getStatusCode());
        self::assertNull($this->repositorio->buscar((int) $autor->codigo));
        self::assertSame('Autor Rubem Fonseca excluído.', $this->sessao->obter('aviso'));
    }

    public function testExclusaoBloqueadaAbreOModalComOsLivrosNaLista(): void
    {
        $autor = $this->repositorio->inserir(Autor::novo('Rubem Fonseca'));
        $this->repositorio->vincularLivros((int) $autor->codigo, ['Agosto', 'Feliz Ano Novo']);

        $this->excluir((int) $autor->codigo);
        $pagina = (string) $this->lista()->getBody();

        self::assertNotNull($this->repositorio->buscar((int) $autor->codigo));
        self::assertStringContainsString('data-abrir-ao-carregar', $pagina);
        self::assertStringContainsString('O autor Rubem Fonseca está vinculado a 2 livros.', $pagina);
        self::assertStringContainsString('<li>Agosto</li>', $pagina);
        self::assertStringContainsString('<li>Feliz Ano Novo</li>', $pagina);
    }

    public function testExcluirAutorInexistenteRespondeNaoEncontrado(): void
    {
        self::assertSame(404, $this->excluir(999)->getStatusCode());
    }

    private function lista(): ResponseInterface
    {
        return (new ListaDeAutoresController($this->visao, $this->cadastro, $this->sessao, $this->formulario))
            ->handle(new ServerRequest('GET', '/autores'));
    }

    private function abrirFormulario(?int $codigo = null): ResponseInterface
    {
        $requisicao = new ServerRequest('GET', '/autores/novo');
        if ($codigo !== null) {
            $requisicao = $requisicao->withAttribute('codigo', (string) $codigo);
        }

        return (new FormularioDeAutorController($this->formulario, $this->cadastro))->handle($requisicao);
    }

    private function salvar(?int $codigo, string $nome): ResponseInterface
    {
        $requisicao = (new ServerRequest('POST', '/autores'))->withParsedBody(['nome' => $nome]);
        if ($codigo !== null) {
            $requisicao = $requisicao->withAttribute('codigo', (string) $codigo);
        }

        return (new SalvarAutorController($this->formulario, $this->cadastro, $this->sessao, $this->visao))
            ->handle($requisicao);
    }

    private function excluir(int $codigo): ResponseInterface
    {
        $requisicao = (new ServerRequest('POST', '/autores/excluir'))->withAttribute('codigo', (string) $codigo);

        return (new ExcluirAutorController($this->formulario, $this->cadastro, $this->sessao, $this->visao))
            ->handle($requisicao);
    }
}
