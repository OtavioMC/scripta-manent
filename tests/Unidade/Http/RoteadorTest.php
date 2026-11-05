<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Http\Roteador;

final class RoteadorTest extends TestCase
{
    public function testEntregaARequisicaoAoHandlerDaRota(): void
    {
        $resposta = $this->roteador()->handle(new ServerRequest('GET', '/versao'));

        self::assertSame('resposta da versão', (string) $resposta->getBody());
    }

    public function testRespondeNaoEncontradoParaCaminhoSemRota(): void
    {
        $resposta = $this->roteador()->handle(new ServerRequest('GET', '/nao-existe'));

        self::assertSame(404, $resposta->getStatusCode());
    }

    public function testRespondeMetodoNaoPermitidoParaMetodoSemRota(): void
    {
        $resposta = $this->roteador()->handle(new ServerRequest('POST', '/versao'));

        self::assertSame(405, $resposta->getStatusCode());
    }

    public function testInformaOsMetodosAceitosQuandoOMetodoNaoEPermitido(): void
    {
        $resposta = $this->roteador()->handle(new ServerRequest('POST', '/versao'));

        self::assertSame('GET', $resposta->getHeaderLine('Allow'));
    }

    public function testRepassaOsParametrosDoCaminhoComoAtributosDaRequisicao(): void
    {
        $resposta = $this->roteador()->handle(new ServerRequest('GET', '/livros/7'));

        self::assertSame('livro 7', (string) $resposta->getBody());
    }

    public function testListaTodosOsMetodosAceitosPeloCaminho(): void
    {
        $resposta = $this->roteador()->handle(new ServerRequest('DELETE', '/livros/7'));

        self::assertSame('GET, POST', $resposta->getHeaderLine('Allow'));
    }

    public function testHeadEmRotaGetRespondeComOHandlerDoGet(): void
    {
        $resposta = $this->roteador()->handle(new ServerRequest('HEAD', '/versao'));

        self::assertSame(200, $resposta->getStatusCode());
    }

    public function testNaoEncontradoDizQualEnderecoNaoExiste(): void
    {
        $resposta = $this->roteador()->handle(new ServerRequest('GET', '/nao-existe'));

        self::assertSame('Nenhuma página responde no endereço /nao-existe.', (string) $resposta->getBody());
    }

    public function testErrosSaoRespondidosEmTextoPuroUtf8(): void
    {
        $resposta = $this->roteador()->handle(new ServerRequest('GET', '/nao-existe'));

        self::assertSame('text/plain; charset=utf-8', $resposta->getHeaderLine('Content-Type'));
    }

    private function roteador(): Roteador
    {
        return new Roteador(new Psr17Factory(), [
            ['GET', '/versao', $this->handlerQueResponde('resposta da versão')],
            ['GET', '/livros/{codl:\d+}', $this->handlerQueMostraOLivro()],
            ['POST', '/livros/{codl:\d+}', $this->handlerQueResponde('livro alterado')],
        ]);
    }

    private function handlerQueMostraOLivro(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $codl = $request->getAttribute('codl');

                return new Response(200, [], 'livro ' . (is_string($codl) ? $codl : 'sem código'));
            }
        };
    }

    private function handlerQueResponde(string $corpo): RequestHandlerInterface
    {
        return new class ($corpo) implements RequestHandlerInterface {
            public function __construct(private readonly string $corpo)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], $this->corpo);
            }
        };
    }
}
