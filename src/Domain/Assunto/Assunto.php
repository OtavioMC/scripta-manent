<?php

declare(strict_types=1);

namespace ScriptaManent\Domain\Assunto;

use ScriptaManent\Domain\ErroDeValidacao;

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
        return new self(null, self::validarDescricao($descricao));
    }

    public static function existente(int $codigo, string $descricao): self
    {
        return new self($codigo, self::validarDescricao($descricao));
    }

    private static function validarDescricao(string $descricao): string
    {
        $descricao = trim($descricao);

        if ($descricao === '') {
            throw new ErroDeValidacao(['descricao' => self::DESCRICAO_OBRIGATORIA]);
        }

        if (mb_strlen($descricao) > self::DESCRICAO_MAXIMA) {
            throw new ErroDeValidacao([
                'descricao' => sprintf('Descrição aceita até %d caracteres.', self::DESCRICAO_MAXIMA),
            ]);
        }

        return $descricao;
    }
}
