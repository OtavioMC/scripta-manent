<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Integracao;

use PDO;
use RuntimeException;

final class ConexaoDeTeste
{
    private static ?PDO $pdo = null;

    /**
     * @param array<string, string|int> $parametros
     * @return list<array<string, string|null>>
     */
    public static function consultar(string $sql, array $parametros = []): array
    {
        $consulta = self::pdo()->prepare($sql);
        $consulta->execute($parametros);

        $linhas = [];
        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $texto = [];
            foreach ((array) $linha as $coluna => $valor) {
                $texto[(string) $coluna] = is_scalar($valor) ? (string) $valor : null;
            }
            $linhas[] = $texto;
        }

        return $linhas;
    }

    /**
     * @param array<string, string|int> $parametros
     */
    public static function inserir(string $sql, array $parametros): int
    {
        self::pdo()->prepare($sql)->execute($parametros);

        return (int) self::pdo()->lastInsertId();
    }

    public static function iniciarTransacao(): void
    {
        self::pdo()->beginTransaction();
    }

    public static function desfazerTransacao(): void
    {
        if (self::pdo()->inTransaction()) {
            self::pdo()->rollBack();
        }
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                self::variavel('BANCO_HOST'),
                self::variavel('BANCO_NOME'),
            );

            self::$pdo = new PDO(
                $dsn,
                self::variavel('BANCO_USUARIO'),
                self::variavel('BANCO_SENHA'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
        }

        return self::$pdo;
    }

    private static function variavel(string $nome): string
    {
        $valor = getenv($nome);
        if ($valor === false || $valor === '') {
            throw new RuntimeException(sprintf('A variável %s não está definida no contêiner dos testes.', $nome));
        }

        return $valor;
    }
}
