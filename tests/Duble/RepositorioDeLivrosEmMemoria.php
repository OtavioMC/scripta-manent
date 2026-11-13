<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Duble;

use ScriptaManent\Application\Busca;
use ScriptaManent\Application\ListaPaginada;
use ScriptaManent\Application\Livro\LivroNaLista;
use ScriptaManent\Application\Livro\RepositorioDeLivros;
use ScriptaManent\Application\Pagina;
use ScriptaManent\Domain\Livro\Livro;

final class RepositorioDeLivrosEmMemoria implements RepositorioDeLivros
{
    public int $listagens = 0;

    /** @var array<int, Livro> */
    private array $livros = [];

    private int $proximoCodigo = 1;

    public function __construct(
        private readonly RepositorioDeAutoresEmMemoria $autores,
        private readonly RepositorioDeAssuntosEmMemoria $assuntos,
    ) {
    }

    public function pagina(Busca $busca, Pagina $pagina): ListaPaginada
    {
        $this->listagens++;
        $encontrados = $this->encontrados($busca);
        $pagina = $pagina->semPassarDaUltima(count($encontrados));

        return new ListaPaginada(
            array_slice($encontrados, $pagina->quantidadeDeItensAntes(), Pagina::ITENS),
            count($encontrados),
            $pagina,
        );
    }

    /**
     * @return list<LivroNaLista>
     */
    private function encontrados(Busca $busca): array
    {
        $lista = array_map(fn (Livro $livro): LivroNaLista => new LivroNaLista(
            (int) $livro->codigo,
            $livro->titulo,
            $livro->editora,
            $livro->edicao,
            $livro->ano->valor,
            $livro->valor,
            array_map(fn (int $codigo): string => $this->autores->buscar($codigo)->nome ?? '', $livro->autores),
            array_map(fn (int $codigo): string => $this->assuntos->buscar($codigo)->descricao ?? '', $livro->assuntos),
            $livro->autores,
            $livro->assuntos,
        ), array_values($this->livros));
        usort($lista, static fn (LivroNaLista $a, LivroNaLista $b): int => strcmp($a->titulo, $b->titulo));

        return array_values(array_filter(
            $lista,
            static fn (LivroNaLista $livro): bool => $busca->vazia()
                || mb_stripos(implode(' ', [$livro->titulo, ...$livro->autores, ...$livro->assuntos]), $busca->texto)
                    !== false,
        ));
    }

    public function buscar(int $codigo): ?Livro
    {
        return $this->livros[$codigo] ?? null;
    }

    public function inserir(Livro $livro): Livro
    {
        $inserido = $livro->comCodigo($this->proximoCodigo++);
        $this->livros[(int) $inserido->codigo] = $inserido;

        return $inserido;
    }

    public function alterar(Livro $livro): void
    {
        $this->livros[(int) $livro->codigo] = $livro;
    }

    public function excluir(int $codigo): void
    {
        unset($this->livros[$codigo]);
    }
}
