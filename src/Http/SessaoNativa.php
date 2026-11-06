<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

use RuntimeException;

final class SessaoNativa implements Sessao
{
    public function obter(string $chave): mixed
    {
        $this->iniciar();

        return $_SESSION[$chave] ?? null;
    }

    public function guardar(string $chave, mixed $valor): void
    {
        $this->iniciar();
        $_SESSION[$chave] = $valor;
    }

    public function retirar(string $chave): mixed
    {
        $this->iniciar();
        $valor = $_SESSION[$chave] ?? null;
        unset($_SESSION[$chave]);

        return $valor;
    }

    private function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $iniciou = session_start([
            'name' => 'sessao',
            'cookie_httponly' => true,
            'cookie_secure' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
            'use_only_cookies' => true,
        ]);
        if (!$iniciou) {
            throw new RuntimeException('Não foi possível iniciar a sessão do PHP.');
        }
    }
}
