<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use Closure;
use PDO;

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
}
