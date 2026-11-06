<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Funcional;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PaginasDeErroTest extends TestCase
{
    public function testEnderecoInexistenteRespondeUmaPaginaHtml(): void
    {
        $resposta = $this->abrir('GET', '/nao-existe');

        self::assertSame(404, $resposta->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $resposta->getHeaderLine('Content-Type'));
    }

    public function testPaginaNaoEncontradaDizQualEnderecoNaoExiste(): void
    {
        $pagina = (string) $this->abrir('GET', '/nao-existe')->getBody();

        self::assertStringContainsString('Nenhuma página responde no endereço /nao-existe.', $pagina);
    }

    public function testMetodoNaoPermitidoRespondeUmaPaginaHtmlComOsMetodosAceitos(): void
    {
        $resposta = $this->abrir('POST', '/versao');

        self::assertSame(405, $resposta->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $resposta->getHeaderLine('Content-Type'));
        self::assertSame('GET', $resposta->getHeaderLine('Allow'));
        self::assertStringContainsString('Métodos aceitos: GET.', (string) $resposta->getBody());
    }

    public function testPaginaDeErroTemLinkParaATelaInicial(): void
    {
        $pagina = (string) $this->abrir('GET', '/nao-existe')->getBody();

        self::assertMatchesRegularExpression('#<a [^>]*href="/"[^>]*>Voltar para a tela inicial</a>#', $pagina);
    }

    public function testPaginaDeErroUsaOLayoutDaAplicacao(): void
    {
        $pagina = (string) $this->abrir('GET', '/nao-existe')->getBody();

        self::assertStringContainsString('href="/assets/css/aplicacao.css"', $pagina);
    }

    private function abrir(string $metodo, string $caminho): ResponseInterface
    {
        $aplicacao = require __DIR__ . '/../../config/aplicacao.php';
        self::assertInstanceOf(RequestHandlerInterface::class, $aplicacao);

        return $aplicacao->handle(new ServerRequest($metodo, $caminho));
    }
}
