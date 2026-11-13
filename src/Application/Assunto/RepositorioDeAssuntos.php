<?php

declare(strict_types=1);

namespace ScriptaManent\Application\Assunto;

use ScriptaManent\Application\Busca;
use ScriptaManent\Application\ListaPaginada;
use ScriptaManent\Application\Pagina;
use ScriptaManent\Domain\Assunto\Assunto;
use ScriptaManent\Domain\ExclusaoBloqueada;

interface RepositorioDeAssuntos
{
    /**
     * @return list<Assunto>
     */
    public function listar(?Busca $busca = null): array;

    /**
     * @return ListaPaginada<Assunto>
     */
    public function pagina(Busca $busca, Pagina $pagina): ListaPaginada;

    public function buscar(int $codigo): ?Assunto;

    public function inserir(Assunto $assunto): Assunto;

    public function alterar(Assunto $assunto): void;

    /**
     * @throws ExclusaoBloqueada
     */
    public function excluir(int $codigo): void;
}
