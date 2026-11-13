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

    /**
     * @return ListaPaginada<string>
     */
    private static function lista(int $totalDeItens, string $paginaPedida): ListaPaginada
    {
        return new ListaPaginada([], $totalDeItens, Pagina::pedidaNaUrl($paginaPedida));
    }
}
