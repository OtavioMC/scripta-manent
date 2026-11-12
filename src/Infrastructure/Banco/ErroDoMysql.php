<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use PDOException;

final class ErroDoMysql
{
    private const string VIOLACAO_DE_INTEGRIDADE = '23000';
    private const string ERRO_GERAL = 'HY000';
    private const string FALHA_DE_SERIALIZACAO = '40001';
    private const int LINHA_REFERENCIADA = 1451;
    private const int REFERENCIA_INEXISTENTE = 1452;
    private const int SERVIDOR_FORA = 2006;
    private const int CONEXAO_PERDIDA = 2013;
    private const int ESPERA_POR_BLOQUEIO_ESGOTADA = 1205;
    private const int DEADLOCK = 1213;

    public static function violouChaveEstrangeira(PDOException $erro): bool
    {
        return self::tem($erro, self::VIOLACAO_DE_INTEGRIDADE, self::LINHA_REFERENCIADA);
    }

    public static function referenciaInexistente(PDOException $erro): bool
    {
        return self::tem($erro, self::VIOLACAO_DE_INTEGRIDADE, self::REFERENCIA_INEXISTENTE);
    }

    public static function conexaoPerdida(PDOException $erro): bool
    {
        return self::tem($erro, self::ERRO_GERAL, self::SERVIDOR_FORA)
            || self::tem($erro, self::ERRO_GERAL, self::CONEXAO_PERDIDA);
    }

    public static function conflitoDeGravacao(PDOException $erro): bool
    {
        return self::tem($erro, self::FALHA_DE_SERIALIZACAO, self::DEADLOCK)
            || self::tem($erro, self::ERRO_GERAL, self::ESPERA_POR_BLOQUEIO_ESGOTADA);
    }

    private static function tem(PDOException $erro, string $sqlstate, int $codigo): bool
    {
        return ($erro->errorInfo[0] ?? null) === $sqlstate
            && ($erro->errorInfo[1] ?? null) === $codigo;
    }
}
