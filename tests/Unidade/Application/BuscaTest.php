<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Application;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Application\Busca;

final class BuscaTest extends TestCase
{
    public function testRemoveEspacosDasPontasEJuntaOsDoMeio(): void
    {
        self::assertSame('Machado de Assis', Busca::deTexto("  Machado   de\tAssis ")->texto);
    }

    public function testLimitaOTamanhoDoTermo(): void
    {
        self::assertSame(Busca::MAXIMO, mb_strlen(Busca::deTexto(str_repeat('á', Busca::MAXIMO + 10))->texto));
    }

    public function testTermoSoComEspacosContaComoBuscaVazia(): void
    {
        self::assertTrue(Busca::deTexto('   ')->vazia());
    }

    public function testNenhumaBuscaEVazia(): void
    {
        self::assertTrue(Busca::nenhuma()->vazia());
        self::assertFalse(Busca::deTexto('Gaiman')->vazia());
    }
}
