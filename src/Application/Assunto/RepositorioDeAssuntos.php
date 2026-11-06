<?php

declare(strict_types=1);

namespace ScriptaManent\Application\Assunto;

use ScriptaManent\Domain\Assunto\Assunto;
use ScriptaManent\Domain\ExclusaoBloqueada;

interface RepositorioDeAssuntos
{
    /**
     * @return list<Assunto>
     */
    public function listar(): array;

    public function buscar(int $codigo): ?Assunto;

    public function inserir(Assunto $assunto): Assunto;

    public function alterar(Assunto $assunto): void;

    /**
     * @throws ExclusaoBloqueada
     */
    public function excluir(int $codigo): void;
}
