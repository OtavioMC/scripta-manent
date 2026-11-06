<?php

declare(strict_types=1);

namespace ScriptaManent\Domain\Autor;

final readonly class Autor
{
    public const int NOME_MAXIMO = 40;

    private function __construct(
        public ?int $codigo,
        public string $nome,
    ) {
    }

    public static function novo(string $nome): self
    {
        return new self(null, $nome);
    }

    public static function existente(int $codigo, string $nome): self
    {
        return new self($codigo, $nome);
    }
}
