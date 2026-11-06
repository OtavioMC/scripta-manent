<?php

declare(strict_types=1);

namespace ScriptaManent\Domain\Autor;

use ScriptaManent\Domain\ErroDeValidacao;

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
        return new self(null, self::validarNome($nome));
    }

    public static function existente(int $codigo, string $nome): self
    {
        return new self($codigo, self::validarNome($nome));
    }

    private static function validarNome(string $nome): string
    {
        $nome = trim($nome);

        if ($nome === '') {
            throw new ErroDeValidacao(['nome' => 'Informe o nome do autor.']);
        }

        if (mb_strlen($nome) > self::NOME_MAXIMO) {
            throw new ErroDeValidacao(['nome' => sprintf('Nome aceita até %d caracteres.', self::NOME_MAXIMO)]);
        }

        return $nome;
    }
}
