<?php

declare(strict_types=1);

namespace ScriptaManent\Domain\Livro;

use ScriptaManent\Domain\ErroDeValidacao;

final readonly class AnoPublicacao
{
    public const string ANO_OBRIGATORIO = 'Informe o ano de publicação.';
    public const string FORMATO = 'Ano de publicação precisa ter 4 dígitos, como 1999.';

    private function __construct(public string $valor)
    {
    }

    public static function deTexto(string $texto): self
    {
        $texto = trim($texto);

        if ($texto === '') {
            throw new ErroDeValidacao(['ano' => self::ANO_OBRIGATORIO]);
        }

        if (preg_match('/\A\d{4}\z/', $texto) !== 1) {
            throw new ErroDeValidacao(['ano' => self::FORMATO]);
        }

        return new self($texto);
    }
}
