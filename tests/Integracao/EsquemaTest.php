<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Integracao;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EsquemaTest extends TestCase
{
    public function testTabelasDoModeloExistem(): void
    {
        $tabelas = $this->valores(
            "SELECT TABLE_NAME AS valor FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'",
        );
        sort($tabelas, SORT_STRING);

        self::assertSame(['Assunto', 'Autor', 'Livro', 'Livro_Assunto', 'Livro_Autor'], $tabelas);
    }

    /**
     * @param list<string> $colunas
     */
    #[DataProvider('colunasDoModelo')]
    public function testColunasSeguemODiagrama(string $tabela, array $colunas): void
    {
        $encontradas = $this->valores(
            "SELECT CONCAT(COLUMN_NAME, ' ', COLUMN_TYPE, IF(IS_NULLABLE = 'NO', ' NOT NULL', ' NULL')) AS valor
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabela
             ORDER BY ORDINAL_POSITION",
            $tabela,
        );

        self::assertSame($colunas, $encontradas);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function colunasDoModelo(): iterable
    {
        yield 'Livro' => ['Livro', [
            'Codl int NOT NULL',
            'Titulo varchar(40) NOT NULL',
            'Editora varchar(40) NOT NULL',
            'Edicao int NOT NULL',
            'AnoPublicacao varchar(4) NOT NULL',
            'Valor decimal(10,2) NOT NULL',
        ]];
        yield 'Autor' => ['Autor', ['CodAu int NOT NULL', 'Nome varchar(40) NOT NULL']];
        yield 'Assunto' => ['Assunto', ['codAs int NOT NULL', 'Descricao varchar(20) NOT NULL']];
        yield 'Livro_Autor' => ['Livro_Autor', ['Livro_Codl int NOT NULL', 'Autor_CodAu int NOT NULL']];
        yield 'Livro_Assunto' => ['Livro_Assunto', ['Livro_Codl int NOT NULL', 'Assunto_codAs int NOT NULL']];
    }

    /**
     * @param list<string> $colunas
     */
    #[DataProvider('chavesPrimariasDoModelo')]
    public function testChavePrimariaSegueODiagrama(string $tabela, array $colunas): void
    {
        $encontradas = $this->valores(
            "SELECT COLUMN_NAME AS valor FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabela AND CONSTRAINT_NAME = 'PRIMARY'
             ORDER BY ORDINAL_POSITION",
            $tabela,
        );

        self::assertSame($colunas, $encontradas);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function chavesPrimariasDoModelo(): iterable
    {
        yield 'Livro' => ['Livro', ['Codl']];
        yield 'Autor' => ['Autor', ['CodAu']];
        yield 'Assunto' => ['Assunto', ['codAs']];
        yield 'Livro_Autor composta' => ['Livro_Autor', ['Livro_Codl', 'Autor_CodAu']];
        yield 'Livro_Assunto composta' => ['Livro_Assunto', ['Livro_Codl', 'Assunto_codAs']];
    }

    /**
     * @return list<string>
     */
    private function valores(string $sql, ?string $tabela = null): array
    {
        $linhas = ConexaoDeTeste::consultar($sql, $tabela === null ? [] : ['tabela' => $tabela]);

        return array_map(static fn (array $linha): string => (string) $linha['valor'], $linhas);
    }
}
