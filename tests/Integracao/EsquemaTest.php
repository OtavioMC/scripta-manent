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

    public function testChavesEstrangeirasSeguemODiagramaSemCascata(): void
    {
        $chaves = $this->valores(
            "SELECT CONCAT(k.CONSTRAINT_NAME, ' ', k.TABLE_NAME, '.', k.COLUMN_NAME, ' -> ',
                    k.REFERENCED_TABLE_NAME, '.', k.REFERENCED_COLUMN_NAME,
                    ' ON DELETE ', r.DELETE_RULE, ' ON UPDATE ', r.UPDATE_RULE) AS valor
             FROM information_schema.KEY_COLUMN_USAGE k
             JOIN information_schema.REFERENTIAL_CONSTRAINTS r
               ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
             WHERE k.TABLE_SCHEMA = DATABASE() AND k.REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY k.CONSTRAINT_NAME",
        );

        self::assertSame([
            'Rel_01 Livro_Autor.Livro_Codl -> Livro.Codl ON DELETE NO ACTION ON UPDATE NO ACTION',
            'Rel_02 Livro_Autor.Autor_CodAu -> Autor.CodAu ON DELETE NO ACTION ON UPDATE NO ACTION',
            'Rel_03 Livro_Assunto.Livro_Codl -> Livro.Codl ON DELETE NO ACTION ON UPDATE NO ACTION',
            'Rel_04 Livro_Assunto.Assunto_codAs -> Assunto.codAs ON DELETE NO ACTION ON UPDATE NO ACTION',
        ], $chaves);
    }

    public function testIndicesSeguemODiagrama(): void
    {
        $indices = $this->valores(
            "SELECT CONCAT(TABLE_NAME, '.', INDEX_NAME, ' (',
                    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX), ')') AS valor
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND INDEX_NAME <> 'PRIMARY'
             GROUP BY TABLE_NAME, INDEX_NAME
             ORDER BY TABLE_NAME, INDEX_NAME",
        );

        self::assertSame([
            'Livro_Assunto.Livro_Assunto_FKIndex1 (Livro_Codl)',
            'Livro_Assunto.Livro_Assunto_FKIndex2 (Assunto_codAs)',
            'Livro_Autor.Livro_Autor_FKIndex1 (Livro_Codl)',
            'Livro_Autor.Livro_Autor_FKIndex2 (Autor_CodAu)',
        ], $indices);
    }

    public function testSoAsChavesSimplesGeramCodigoAutomatico(): void
    {
        $colunas = $this->valores(
            "SELECT CONCAT(TABLE_NAME, '.', COLUMN_NAME) AS valor FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND EXTRA LIKE '%auto_increment%'
             ORDER BY TABLE_NAME",
        );

        self::assertSame(['Assunto.codAs', 'Autor.CodAu', 'Livro.Codl'], $colunas);
    }

    public function testTabelasUsamInnoDbEUtf8mb4(): void
    {
        $tabelas = $this->valores(
            "SELECT CONCAT(TABLE_NAME, ' ', ENGINE, ' ', LEFT(TABLE_COLLATION, 7)) AS valor
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
             ORDER BY TABLE_NAME",
        );

        self::assertSame([
            'Assunto InnoDB utf8mb4',
            'Autor InnoDB utf8mb4',
            'Livro InnoDB utf8mb4',
            'Livro_Assunto InnoDB utf8mb4',
            'Livro_Autor InnoDB utf8mb4',
        ], $tabelas);
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
