<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Duble;

use ScriptaManent\Application\Busca;
use ScriptaManent\Application\Livro\LivroNaLista;
use ScriptaManent\Application\Livro\RepositorioDeLivros;
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

    public function listar(?Busca $busca = null): array
    {
        $this->listagens++;
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
            static fn (LivroNaLista $livro): bool => $busca === null || $busca->vazia()
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
