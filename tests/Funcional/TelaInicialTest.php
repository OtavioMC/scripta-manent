<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Funcional;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TelaInicialTest extends TestCase
{
    public function testRespondeUmaPaginaHtml(): void
    {
        $resposta = $this->abrirTelaInicial();

        self::assertSame(200, $resposta->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $resposta->getHeaderLine('Content-Type'));
    }

    public function testMenuLevaATodasAsTelas(): void
    {
        $pagina = (string) $this->abrirTelaInicial()->getBody();

        foreach (['/livros', '/autores', '/assuntos', '/relatorio'] as $endereco) {
            self::assertStringContainsString(sprintf('href="%s"', $endereco), $pagina);
        }
    }

    public function testCarregaOBootstrapEOCssDaAplicacao(): void
    {
        $pagina = (string) $this->abrirTelaInicial()->getBody();

        self::assertStringContainsString('href="/assets/bootstrap/css/bootstrap.min.css"', $pagina);
        self::assertStringContainsString('href="/assets/css/aplicacao.css"', $pagina);
        self::assertStringContainsString('src="/assets/bootstrap/js/bootstrap.bundle.min.js"', $pagina);
    }

    private function abrirTelaInicial(): ResponseInterface
    {
        $aplicacao = require __DIR__ . '/../../config/aplicacao.php';
        self::assertInstanceOf(RequestHandlerInterface::class, $aplicacao);

        return $aplicacao->handle(new ServerRequest('GET', '/'));
    }
}
