<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Application;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Application\Pagina;

final class PaginaTest extends TestCase
{
    public function testSemNumeroNaConsultaComecaNaPrimeiraPagina(): void
    {
        self::assertSame(1, Pagina::pedidaNaUrl('')->numero);
    }

    public function testNumeroDaConsultaViraAPagina(): void
    {
        self::assertSame(3, Pagina::pedidaNaUrl('3')->numero);
    }

    public function testPrimeiraPaginaComecaNoPrimeiroItem(): void
    {
        self::assertSame(0, Pagina::pedidaNaUrl('1')->quantidadeDeItensAntes());
    }

    public function testPaginaPulaOsItensDasPaginasAnteriores(): void
    {
        self::assertSame(2 * Pagina::ITENS, Pagina::pedidaNaUrl('3')->quantidadeDeItensAntes());
    }

    public function testPaginaDentroDoTotalContinuaIgual(): void
    {
        self::assertSame(2, Pagina::pedidaNaUrl('2')->semPassarDaUltima(3 * Pagina::ITENS)->numero);
    }

    public function testPaginaAlemDaUltimaVoltaParaAUltima(): void
    {
        self::assertSame(4, Pagina::pedidaNaUrl('9')->semPassarDaUltima(3 * Pagina::ITENS + 1)->numero);
    }

    public function testListaVaziaFicaNaPrimeiraPagina(): void
    {
        self::assertSame(1, Pagina::pedidaNaUrl('3')->semPassarDaUltima(0)->numero);
    }

    public function testPaginaCheiaOcupaUmaPaginaSo(): void
    {
        self::assertSame(1, Pagina::quantidadeDePaginas(Pagina::ITENS));
    }

    public function testUmItemAlemDaPaginaCheiaAbreASegundaPagina(): void
    {
        self::assertSame(2, Pagina::quantidadeDePaginas(Pagina::ITENS + 1));
    }

    public function testDuasPaginasCheiasNaoAbremUmaTerceira(): void
    {
        self::assertSame(2, Pagina::quantidadeDePaginas(2 * Pagina::ITENS));
    }

    public function testSegundaPaginaDeUmaPaginaCheiaVoltaParaAPrimeira(): void
    {
        self::assertSame(1, Pagina::pedidaNaUrl('2')->semPassarDaUltima(Pagina::ITENS)->numero);
    }

    public function testNumeroComLetrasVoltaParaAPrimeiraPagina(): void
    {
        self::assertSame(1, Pagina::pedidaNaUrl('2abc')->numero);
    }

    public function testNumeroNegativoVoltaParaAPrimeiraPagina(): void
    {
        self::assertSame(1, Pagina::pedidaNaUrl('-3')->numero);
    }

    public function testNumeroEnormeNaoEstouraAQuantidadeDeItensAntes(): void
    {
        self::assertGreaterThan(0, Pagina::pedidaNaUrl('99999999999999999999')->quantidadeDeItensAntes());
    }
}
