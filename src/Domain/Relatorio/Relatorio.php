<?php

declare(strict_types=1);

namespace ScriptaManent\Domain\Relatorio;

use ScriptaManent\Domain\Livro\Dinheiro;

final readonly class Relatorio
{
    /**
     * @param list<GrupoDeLivros> $porAutor
     * @param list<GrupoDeLivros> $coautorias
     */
    private function __construct(
        public array $porAutor,
        public ?GrupoDeLivros $semAutor,
        public int $totalDeLivros,
        public Dinheiro $valorTotal,
        public array $coautorias,
    ) {
    }

    /**
     * @param list<LinhaDoRelatorio> $linhas
     */
    public static function montar(array $linhas): self
    {
        return new self([], null, 0, Dinheiro::doBanco('0.00'), []);
    }
}
