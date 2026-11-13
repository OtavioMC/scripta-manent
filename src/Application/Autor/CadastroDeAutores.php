<?php

declare(strict_types=1);

namespace ScriptaManent\Application\Autor;

use ScriptaManent\Application\Busca;
use ScriptaManent\Application\ListaPaginada;
use ScriptaManent\Application\Pagina;
use ScriptaManent\Domain\Autor\Autor;
use ScriptaManent\Domain\ErroDeValidacao;
use ScriptaManent\Domain\ExclusaoBloqueada;

final readonly class CadastroDeAutores
{
    public function __construct(private RepositorioDeAutores $repositorio)
    {
    }

    /**
     * @return list<Autor>
     */
    public function listar(?Busca $busca = null): array
    {
        return $this->repositorio->listar($busca);
    }

    /**
     * @return ListaPaginada<Autor>
     */
    public function pagina(Busca $busca, Pagina $pagina): ListaPaginada
    {
        return $this->repositorio->pagina($busca, $pagina);
    }

    public function buscar(int $codigo): ?Autor
    {
        return $this->repositorio->buscar($codigo);
    }

    /**
     * @throws ErroDeValidacao
     */
    public function cadastrar(string $nome): Autor
    {
        return $this->repositorio->inserir(Autor::novo($nome));
    }

    /**
     * @throws ErroDeValidacao
     */
    public function alterar(int $codigo, string $nome): Autor
    {
        $autor = Autor::existente($codigo, $nome);
        $this->repositorio->alterar($autor);

        return $autor;
    }

    /**
     * @throws ExclusaoBloqueada
     */
    public function excluir(int $codigo): void
    {
        $this->repositorio->excluir($codigo);
    }
}
