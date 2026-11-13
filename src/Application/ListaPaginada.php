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
        return Pagina::quantidadeDePaginas($this->totalDeItens);
    }

    public function paginaAnterior(): ?int
    {
        return $this->pagina->numero > 1 ? $this->pagina->numero - 1 : null;
    }

    public function proximaPagina(): ?int
    {
        return $this->pagina->numero < $this->quantidadeDePaginas() ? $this->pagina->numero + 1 : null;
    }

    /**
     * @return list<int|null>
     */
    public function paginasNaNavegacao(): array
    {
        return [];
    }

    public function primeiroItemExibido(): int
    {
        return $this->totalDeItens === 0 ? 0 : $this->pagina->quantidadeDeItensAntes() + 1;
    }

    public function ultimoItemExibido(): int
    {
        return min($this->pagina->quantidadeDeItensAntes() + Pagina::ITENS, $this->totalDeItens);
    }
}
