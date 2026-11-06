<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Integracao;

use PDO;
use RuntimeException;

final class ConexaoDeTeste
{
    /**
     * @param array<string, string> $parametros
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

    private static function pdo(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            self::variavel('BANCO_HOST'),
            self::variavel('BANCO_NOME'),
        );

        return new PDO(
            $dsn,
            self::variavel('BANCO_USUARIO'),
            self::variavel('BANCO_SENHA'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
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
