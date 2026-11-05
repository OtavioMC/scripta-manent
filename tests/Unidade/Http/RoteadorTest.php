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
        $roteador = new Roteador(new Psr17Factory(), [
            ['GET', '/versao', $this->handlerQueResponde('resposta da versão')],
        ]);

        $resposta = $roteador->handle(new ServerRequest('GET', '/versao'));

        self::assertSame('resposta da versão', (string) $resposta->getBody());
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
