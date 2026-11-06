<?php

declare(strict_types=1);

namespace ScriptaManent\Application;

final readonly class Busca
{
    public const int MAXIMO = 60;

    private function __construct(public string $texto)
    {
    }

    public static function deTexto(string $texto): self
    {
        $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? '');

        return new self(mb_substr($texto, 0, self::MAXIMO));
    }

    public static function nenhuma(): self
    {
        return new self('');
    }

    public function vazia(): bool
    {
        return $this->texto === '';
    }
}
