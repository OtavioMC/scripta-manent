<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

use RuntimeException;

final class SessaoNativa implements Sessao
{
    private const string NOME = 'sessao';

    public function __construct(private readonly int $duracaoEmSegundos)
    {
    }

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

    public function renovarIdentificador(): void
    {
        $this->iniciar();
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Não foi possível renovar o identificador da sessão.');
        }
    }

    public function encerrar(): void
    {
        $this->iniciar();
        $_SESSION = [];
        session_destroy();
        setcookie(self::NOME, '', [
            'expires' => 1,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $iniciou = session_start([
            'name' => self::NOME,
            'cookie_httponly' => true,
            'cookie_secure' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
            'use_only_cookies' => true,
            'gc_maxlifetime' => $this->duracaoEmSegundos,
        ]);
        if (!$iniciou) {
            throw new RuntimeException('Não foi possível iniciar a sessão do PHP.');
        }
    }
}
