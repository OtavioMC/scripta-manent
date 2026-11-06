<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use PDOException;

final class ErroDoMysql
{
    private const string VIOLACAO_DE_INTEGRIDADE = '23000';
    private const int LINHA_REFERENCIADA = 1451;

    public static function violouChaveEstrangeira(PDOException $erro): bool
    {
        return ($erro->errorInfo[0] ?? null) === self::VIOLACAO_DE_INTEGRIDADE
            && ($erro->errorInfo[1] ?? null) === self::LINHA_REFERENCIADA;
    }
}
