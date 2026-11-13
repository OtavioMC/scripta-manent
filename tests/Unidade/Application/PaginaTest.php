<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Application;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Application\Pagina;

final class PaginaTest extends TestCase
{
    public function testSemNumeroNaConsultaComecaNaPrimeiraPagina(): void
    {
        self::assertSame(1, Pagina::daConsulta('')->numero);
    }

    public function testNumeroDaConsultaViraAPagina(): void
    {
        self::assertSame(3, Pagina::daConsulta('3')->numero);
    }

    public function testPrimeiraPaginaComecaNoPrimeiroItem(): void
    {
        self::assertSame(0, Pagina::daConsulta('1')->deslocamento());
    }

    public function testDeslocamentoPulaOsItensDasPaginasAnteriores(): void
    {
        self::assertSame(2 * Pagina::ITENS, Pagina::daConsulta('3')->deslocamento());
    }

    public function testPaginaDentroDoTotalContinuaIgual(): void
    {
        self::assertSame(2, Pagina::daConsulta('2')->limitadaA(3 * Pagina::ITENS)->numero);
    }

    public function testPaginaAlemDaUltimaVoltaParaAUltima(): void
    {
        self::assertSame(4, Pagina::daConsulta('9')->limitadaA(3 * Pagina::ITENS + 1)->numero);
    }

    public function testListaVaziaFicaNaPrimeiraPagina(): void
    {
        self::assertSame(1, Pagina::daConsulta('3')->limitadaA(0)->numero);
    }

    public function testNumeroComLetrasVoltaParaAPrimeiraPagina(): void
    {
        self::assertSame(1, Pagina::daConsulta('2abc')->numero);
    }

    public function testNumeroNegativoVoltaParaAPrimeiraPagina(): void
    {
        self::assertSame(1, Pagina::daConsulta('-3')->numero);
    }

    public function testNumeroEnormeNaoEstouraODeslocamento(): void
    {
        self::assertGreaterThan(0, Pagina::daConsulta('99999999999999999999')->deslocamento());
    }
}
