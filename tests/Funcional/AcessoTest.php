<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Funcional;

use PHPUnit\Framework\TestCase;

final class AcessoTest extends TestCase
{
    public function testSemLoginATelaInicialLevaParaOLogin(): void
    {
        $resposta = Aplicacao::abrir('GET', '/', logado: false);

        self::assertSame(303, $resposta->getStatusCode());
        self::assertSame('/login', $resposta->getHeaderLine('Location'));
    }

    public function testTelaDeLoginAbreSemLogin(): void
    {
        $resposta = Aplicacao::abrir('GET', '/login', logado: false);

        self::assertSame(200, $resposta->getStatusCode());
        self::assertStringContainsString('<h1 class="h4 mb-3">Entrar</h1>', (string) $resposta->getBody());
    }

    public function testEnvioDeFormularioSemLoginTambemLevaParaOLogin(): void
    {
        self::assertSame('/login', Aplicacao::abrir('POST', '/autores', logado: false)->getHeaderLine('Location'));
    }
}
