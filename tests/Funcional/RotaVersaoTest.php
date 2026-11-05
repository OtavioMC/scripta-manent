<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Funcional;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

final class RotaVersaoTest extends TestCase
{
    public function testRespondeDevLocalmente(): void
    {
        $aplicacao = require __DIR__ . '/../../config/aplicacao.php';
        self::assertInstanceOf(RequestHandlerInterface::class, $aplicacao);

        $resposta = $aplicacao->handle(new ServerRequest('GET', '/versao'));

        self::assertSame(200, $resposta->getStatusCode());
        self::assertSame('dev', (string) $resposta->getBody());
    }
}
