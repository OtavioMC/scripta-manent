<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http\Controller;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use ScriptaManent\Http\Controller\VersaoController;

final class VersaoControllerTest extends TestCase
{
    public function testRespondeComStatus200(): void
    {
        $controller = new VersaoController(new Psr17Factory(), 'abc1234');

        $resposta = $controller->handle(new ServerRequest('GET', '/versao'));

        self::assertSame(200, $resposta->getStatusCode());
    }
}
