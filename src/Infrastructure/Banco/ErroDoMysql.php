<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use PDOException;

final class ErroDoMysql
{
    private const string VIOLACAO_DE_INTEGRIDADE = '23000';
    private const int LINHA_REFERENCIADA = 1451;
    private const int REFERENCIA_INEXISTENTE = 1452;

    public static function violouChaveEstrangeira(PDOException $erro): bool
    {
        return self::violacao($erro, self::LINHA_REFERENCIADA);
    }

    public static function referenciaInexistente(PDOException $erro): bool
    {
        return self::violacao($erro, self::REFERENCIA_INEXISTENTE);
    }

    private static function violacao(PDOException $erro, int $codigo): bool
    {
        return ($erro->errorInfo[0] ?? null) === self::VIOLACAO_DE_INTEGRIDADE
            && ($erro->errorInfo[1] ?? null) === $codigo;
    }
}
