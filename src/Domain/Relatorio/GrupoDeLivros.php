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
        return '';
    }

    public function quantidade(): int
    {
        return 0;
    }

    public function valor(): Dinheiro
    {
        return Dinheiro::doBanco('0.00');
    }
}
