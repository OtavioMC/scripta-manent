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
}
