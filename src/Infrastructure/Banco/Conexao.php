<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use Closure;
use PDO;
use PDOException;
use ScriptaManent\Application\BancoIndisponivel;
use Throwable;

final class Conexao
{
    private ?PDO $pdo = null;

    /**
     * @param Closure(): PDO $abrir
     */
    public function __construct(private readonly Closure $abrir)
    {
    }

    public function pdo(): PDO
    {
        try {
            return $this->pdo ??= ($this->abrir)();
        } catch (PDOException $erro) {
            throw new BancoIndisponivel('A conexão com o MySQL falhou.', 0, $erro);
        }
    }

    /**
     * @template T
     * @param callable(PDO): T $operacao
     * @return T
     */
    public function emTransacao(callable $operacao): mixed
    {
        $pdo = $this->pdo();
        if ($pdo->inTransaction()) {
            return $operacao($pdo);
        }

        $pdo->beginTransaction();
        try {
            $resultado = $operacao($pdo);
            $pdo->commit();

            return $resultado;
        } catch (Throwable $erro) {
            $pdo->rollBack();

            throw $erro;
        }
    }
}
