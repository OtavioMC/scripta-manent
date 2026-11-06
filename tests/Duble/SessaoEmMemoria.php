<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Duble;

use ScriptaManent\Http\Sessao;

final class SessaoEmMemoria implements Sessao
{
    /** @var array<string, mixed> */
    private array $valores = [];

    public function obter(string $chave): mixed
    {
        return $this->valores[$chave] ?? null;
    }

    public function guardar(string $chave, mixed $valor): void
    {
        $this->valores[$chave] = $valor;
    }

    public function retirar(string $chave): mixed
    {
        $valor = $this->valores[$chave] ?? null;
        unset($this->valores[$chave]);

        return $valor;
    }
}
