<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Integracao;

use PHPUnit\Framework\TestCase;

final class EsquemaTest extends TestCase
{
    public function testTabelasDoModeloExistem(): void
    {
        $linhas = ConexaoDeTeste::consultar(
            "SELECT TABLE_NAME AS nome FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'",
        );
        $tabelas = array_map(static fn (array $linha): string => (string) $linha['nome'], $linhas);
        sort($tabelas, SORT_STRING);

        self::assertSame(['Assunto', 'Autor', 'Livro', 'Livro_Assunto', 'Livro_Autor'], $tabelas);
    }
}
