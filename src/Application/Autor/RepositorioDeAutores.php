<?php

declare(strict_types=1);

namespace ScriptaManent\Application\Autor;

use ScriptaManent\Domain\Autor\Autor;
use ScriptaManent\Domain\ExclusaoBloqueada;

interface RepositorioDeAutores
{
    /**
     * @return list<Autor>
     */
    public function listar(): array;

    public function buscar(int $codigo): ?Autor;

    public function inserir(Autor $autor): Autor;

    public function alterar(Autor $autor): void;

    /**
     * @throws ExclusaoBloqueada
     */
    public function excluir(int $codigo): void;
}
