<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Funcional;

use PHPUnit\Framework\TestCase;

final class RotaVersaoTest extends TestCase
{
    public function testRespondeDevLocalmente(): void
    {
        $resposta = Aplicacao::abrir('GET', '/versao', logado: false);

        self::assertSame(200, $resposta->getStatusCode());
        self::assertSame('dev', (string) $resposta->getBody());
    }
}
