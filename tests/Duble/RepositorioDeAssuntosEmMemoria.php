<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Duble;

use ScriptaManent\Application\Assunto\RepositorioDeAssuntos;
use ScriptaManent\Application\Busca;
use ScriptaManent\Domain\Assunto\Assunto;
use ScriptaManent\Domain\ExclusaoBloqueada;

final class RepositorioDeAssuntosEmMemoria implements RepositorioDeAssuntos
{
    /** @var array<int, Assunto> */
    private array $assuntos = [];

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
        $assuntos = array_values($this->assuntos);
        usort($assuntos, static fn (Assunto $a, Assunto $b): int => strcmp($a->descricao, $b->descricao));

        return array_values(array_filter(
            $assuntos,
            static fn (Assunto $assunto): bool => $busca === null || $busca->vazia()
                || mb_stripos($assunto->descricao, $busca->texto) !== false,
        ));
    }

    public function buscar(int $codigo): ?Assunto
    {
        return $this->assuntos[$codigo] ?? null;
    }

    public function inserir(Assunto $assunto): Assunto
    {
        $inserido = Assunto::existente($this->proximoCodigo++, $assunto->descricao);
        $this->assuntos[(int) $inserido->codigo] = $inserido;

        return $inserido;
    }

    public function alterar(Assunto $assunto): void
    {
        $this->assuntos[(int) $assunto->codigo] = $assunto;
    }

    public function excluir(int $codigo): void
    {
        if (($this->livros[$codigo] ?? []) !== []) {
            throw ExclusaoBloqueada::doAssunto($this->assuntos[$codigo]->descricao, $this->livros[$codigo]);
        }

        unset($this->assuntos[$codigo]);
    }
}
