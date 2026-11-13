<?php

declare(strict_types=1);

namespace ScriptaManent\Application;

/**
 * @template T
 */
final readonly class ListaPaginada
{
    /**
     * @param list<T> $itens
     */
    public function __construct(
        public array $itens,
        public int $totalDeItens,
        public Pagina $pagina,
    ) {
    }

    public function quantidadeDePaginas(): int
    {
        return 0;
    }
}
