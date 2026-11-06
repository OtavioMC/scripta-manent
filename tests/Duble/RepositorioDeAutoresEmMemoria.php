<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Duble;

use ScriptaManent\Application\Autor\RepositorioDeAutores;
use ScriptaManent\Application\Busca;
use ScriptaManent\Domain\Autor\Autor;
use ScriptaManent\Domain\ExclusaoBloqueada;

final class RepositorioDeAutoresEmMemoria implements RepositorioDeAutores
{
    /** @var array<int, Autor> */
    private array $autores = [];

    /** @var array<int, list<string>> */
    private array $livros = [];

    private int $proximoCodigo = 1;

    /**
     * @param list<string> $titulos
     */
    public function vincularLivros(int $codigo, array $titulos): void
    {
        $this->livros[$codigo] = $titulos;
    }

    public function listar(?Busca $busca = null): array
    {
        $autores = array_values($this->autores);
        usort($autores, static fn (Autor $a, Autor $b): int => strcmp($a->nome, $b->nome));

        return array_values(array_filter(
            $autores,
            static fn (Autor $autor): bool => $busca === null || $busca->vazia()
                || mb_stripos($autor->nome, $busca->texto) !== false,
        ));
    }

    public function buscar(int $codigo): ?Autor
    {
        return $this->autores[$codigo] ?? null;
    }

    public function inserir(Autor $autor): Autor
    {
        $inserido = Autor::existente($this->proximoCodigo++, $autor->nome);
        $this->autores[(int) $inserido->codigo] = $inserido;

        return $inserido;
    }

    public function alterar(Autor $autor): void
    {
        $this->autores[(int) $autor->codigo] = $autor;
    }

    public function excluir(int $codigo): void
    {
        if (($this->livros[$codigo] ?? []) !== []) {
            throw ExclusaoBloqueada::doAutor($this->autores[$codigo]->nome, $this->livros[$codigo]);
        }

        unset($this->autores[$codigo]);
    }
}
