<?php

declare(strict_types=1);

namespace ScriptaManent\Domain\Livro;

final readonly class AnoPublicacao
{
    public const string ANO_OBRIGATORIO = 'Informe o ano de publicação.';
    public const string FORMATO = 'Ano de publicação precisa ter 4 dígitos, como 1999.';

    private function __construct(public string $valor)
    {
    }

    public static function deTexto(string $texto): self
    {
        return new self($texto);
    }
}
