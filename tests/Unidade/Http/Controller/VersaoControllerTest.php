<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http\Controller;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ScriptaManent\Http\Controller\VersaoController;

final class VersaoControllerTest extends TestCase
{
    public function testRespondeComStatus200(): void
    {
        self::assertSame(200, $this->responder('abc1234')->getStatusCode());
    }

    public function testCorpoTrazAVersao(): void
    {
        self::assertSame('abc1234', (string) $this->responder('abc1234')->getBody());
    }

    public function testRespondeEmTextoPuroUtf8(): void
    {
        self::assertSame(
            'text/plain; charset=utf-8',
            $this->responder('abc1234')->getHeaderLine('Content-Type'),
        );
    }

    public function testRespostaNaoEGuardadaEmCache(): void
    {
        self::assertSame('no-store', $this->responder('abc1234')->getHeaderLine('Cache-Control'));
    }

    private function responder(string $versao): ResponseInterface
    {
        $controller = new VersaoController(new Psr17Factory(), $versao);

        return $controller->handle(new ServerRequest('GET', '/versao'));
    }
}
