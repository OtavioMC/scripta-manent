<?php

declare(strict_types=1);

namespace ScriptaManent\Application\Livro;

use ScriptaManent\Application\Busca;
use ScriptaManent\Application\ListaPaginada;
use ScriptaManent\Application\Pagina;
use ScriptaManent\Domain\ErroDeValidacao;
use ScriptaManent\Domain\Livro\Livro;

interface RepositorioDeLivros
{
    /**
     * @return ListaPaginada<LivroNaLista>
     */
    public function pagina(Busca $busca, Pagina $pagina): ListaPaginada;

    public function buscar(int $codigo): ?Livro;

    /**
     * @throws ErroDeValidacao
     */
    public function inserir(Livro $livro): Livro;

    /**
     * @throws ErroDeValidacao
     */
    public function alterar(Livro $livro): void;

    public function excluir(int $codigo): void;
}
