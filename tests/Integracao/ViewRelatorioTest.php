<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Integracao;

use PHPUnit\Framework\TestCase;

final class ViewRelatorioTest extends TestCase
{
    public function testViewDoRelatorioExiste(): void
    {
        $views = ConexaoDeTeste::consultar(
            "SELECT TABLE_NAME FROM information_schema.VIEWS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vw_livros_por_autor'",
        );

        self::assertCount(1, $views);
    }
}
