<?php

declare(strict_types=1);

namespace ScriptaManent\Domain\Assunto;

final readonly class Assunto
{
    public const int DESCRICAO_MAXIMA = 20;
    public const string DESCRICAO_OBRIGATORIA = 'Informe a descrição do assunto.';

    private function __construct(
        public ?int $codigo,
        public string $descricao,
    ) {
    }

    public static function novo(string $descricao): self
    {
        return new self(null, $descricao);
    }

    public static function existente(int $codigo, string $descricao): self
    {
        return new self($codigo, $descricao);
    }
}
