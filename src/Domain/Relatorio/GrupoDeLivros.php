<?php

declare(strict_types=1);

namespace ScriptaManent\Domain\Relatorio;

use ScriptaManent\Domain\Livro\Dinheiro;

final readonly class GrupoDeLivros
{
    /**
     * @param list<string> $autores
     * @param list<LivroDoRelatorio> $livros
     */
    public function __construct(
        public array $autores,
        public array $livros,
    ) {
    }

    public function titulo(): string
    {
        $autores = $this->autores;
        $ultimo = array_pop($autores);
        if ($ultimo === null) {
            return '';
        }

        return $autores === [] ? $ultimo : implode(', ', $autores) . ' e ' . $ultimo;
    }

    public function quantidade(): int
    {
        return count($this->livros);
    }

    public function valor(): Dinheiro
    {
        return Relatorio::somar($this->livros);
    }
}
