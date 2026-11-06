<?php

declare(strict_types=1);

namespace ScriptaManent\Domain;

use DomainException;

final class ExclusaoBloqueada extends DomainException
{
    /**
     * @param list<string> $titulos
     */
    private function __construct(string $mensagem, public readonly array $titulos)
    {
        parent::__construct($mensagem);
    }

    /**
     * @param list<string> $titulos
     */
    public static function doAutor(string $nome, array $titulos): self
    {
        return new self(sprintf(
            'O autor %s está vinculado a %s. Remova os vínculos antes de excluir.',
            $nome,
            self::quantidadeDeLivros(count($titulos)),
        ), $titulos);
    }

    private static function quantidadeDeLivros(int $quantidade): string
    {
        return $quantidade === 1 ? '1 livro' : sprintf('%d livros', $quantidade);
    }
}
