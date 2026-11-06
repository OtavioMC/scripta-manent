<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http\Controller;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use ScriptaManent\Application\Cache\SemVersoes;
use ScriptaManent\Application\Cache\VersoesDasColecoes;
use ScriptaManent\Http\Controller\VersoesController;

final class VersoesControllerTest extends TestCase
{
    public function testDevolveAsVersoesEmJsonSemGuardarNoNavegador(): void
    {
        $versoes = new class implements VersoesDasColecoes {
            public function atuais(): array
            {
                return ['autores' => 3, 'livros' => 7];
            }
        };

        $resposta = (new VersoesController(new Psr17Factory(), $versoes))->handle(new ServerRequest('GET', '/versoes'));

        self::assertSame('{"autores":3,"livros":7}', (string) $resposta->getBody());
        self::assertSame('application/json; charset=utf-8', $resposta->getHeaderLine('Content-Type'));
        self::assertSame('no-store', $resposta->getHeaderLine('Cache-Control'));
    }

    public function testSemRedisDevolveUmObjetoVazio(): void
    {
        $resposta = (new VersoesController(new Psr17Factory(), new SemVersoes()))
            ->handle(new ServerRequest('GET', '/versoes'));

        self::assertSame('{}', (string) $resposta->getBody());
    }
}
