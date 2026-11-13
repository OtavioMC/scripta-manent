<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Application;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Application\ListaPaginada;
use ScriptaManent\Application\Pagina;

final class ListaPaginadaTest extends TestCase
{
    public function testUmItemAlemDeTresPaginasCheiasOcupaQuatroPaginas(): void
    {
        self::assertSame(4, self::lista(3 * Pagina::ITENS + 1, '1')->quantidadeDePaginas());
    }

    public function testPrimeiraPaginaNaoTemPaginaAnterior(): void
    {
        self::assertNull(self::lista(3 * Pagina::ITENS + 1, '1')->paginaAnterior());
    }

    public function testTerceiraPaginaTemASegundaComoAnterior(): void
    {
        self::assertSame(2, self::lista(3 * Pagina::ITENS + 1, '3')->paginaAnterior());
    }

    public function testPrimeiraPaginaTemASegundaComoProxima(): void
    {
        self::assertSame(2, self::lista(3 * Pagina::ITENS + 1, '1')->proximaPagina());
    }

    public function testUltimaPaginaNaoTemProxima(): void
    {
        self::assertNull(self::lista(3 * Pagina::ITENS + 1, '4')->proximaPagina());
    }

    public function testPoucasPaginasAparecemTodasNaNavegacao(): void
    {
        self::assertSame([1, 2, 3, 4], self::lista(3 * Pagina::ITENS + 1, '2')->paginasNaNavegacao());
    }

    public function testUmaPaginaSoMostraSoONumeroUm(): void
    {
        self::assertSame([1], self::lista(Pagina::ITENS, '1')->paginasNaNavegacao());
    }

    public function testDuasPaginasMostramSoUmEDois(): void
    {
        self::assertSame([1, 2], self::lista(Pagina::ITENS + 1, '2')->paginasNaNavegacao());
    }

    public function testPaginaNoMeioDeMuitasMostraAsVizinhasEReticenciasNosSaltos(): void
    {
        self::assertSame([1, null, 4, 5, 6, null, 10], self::lista(10 * Pagina::ITENS, '5')->paginasNaNavegacao());
    }

    public function testPrimeiraPaginaDeMuitasMostraASegundaEAUltima(): void
    {
        self::assertSame([1, 2, null, 10], self::lista(10 * Pagina::ITENS, '1')->paginasNaNavegacao());
    }

    public function testUltimaPaginaDeMuitasMostraAPrimeiraEAPenultima(): void
    {
        self::assertSame([1, null, 9, 10], self::lista(10 * Pagina::ITENS, '10')->paginasNaNavegacao());
    }

    public function testSaltoDeUmaPaginaSoMostraONumeroNoLugarDasReticencias(): void
    {
        self::assertSame([1, 2, 3, 4, 5, null, 10], self::lista(10 * Pagina::ITENS, '4')->paginasNaNavegacao());
    }

    public function testNenhumNumeroPassaDaUltimaPagina(): void
    {
        for ($pagina = 1; $pagina <= 10; $pagina++) {
            $numeros = array_filter(self::lista(10 * Pagina::ITENS, (string) $pagina)->paginasNaNavegacao());

            self::assertSame(10, max([0, ...$numeros]), sprintf('Página %d', $pagina));
        }
    }

    public function testSegundaPaginaExibeOsItensDepoisDaPrimeiraPaginaCheia(): void
    {
        $lista = self::lista(3 * Pagina::ITENS + 1, '2');

        self::assertSame(Pagina::ITENS + 1, $lista->primeiroItemExibido());
        self::assertSame(2 * Pagina::ITENS, $lista->ultimoItemExibido());
    }

    public function testUltimaPaginaIncompletaTerminaNoUltimoItem(): void
    {
        $lista = self::lista(3 * Pagina::ITENS + 1, '4');

        self::assertSame(3 * Pagina::ITENS + 1, $lista->primeiroItemExibido());
        self::assertSame(3 * Pagina::ITENS + 1, $lista->ultimoItemExibido());
    }

    public function testListaVaziaNaoExibeNenhumItem(): void
    {
        $lista = self::lista(0, '1');

        self::assertSame(0, $lista->primeiroItemExibido());
        self::assertSame(0, $lista->ultimoItemExibido());
    }

    /**
     * @return ListaPaginada<string>
     */
    private static function lista(int $totalDeItens, string $paginaPedida): ListaPaginada
    {
        return new ListaPaginada([], $totalDeItens, Pagina::pedidaNaUrl($paginaPedida));
    }
}
