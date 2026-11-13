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

    public function paginaAnterior(): ?int
    {
        return $this->totalDeItens === 0 ? null : $this->pagina->numero;
    }

    public function proximaPagina(): ?int
    {
        return $this->totalDeItens === 0 ? null : $this->pagina->numero;
    }

    public function primeiroItemExibido(): int
    {
        return 0;
    }

    public function ultimoItemExibido(): int
    {
        return 0;
    }
}
