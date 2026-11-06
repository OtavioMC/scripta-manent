<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use Closure;
use PDO;
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
        return $this->pdo ??= ($this->abrir)();
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
