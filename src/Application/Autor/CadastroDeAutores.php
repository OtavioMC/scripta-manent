<?php

declare(strict_types=1);

namespace ScriptaManent\Application\Autor;

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
    public function listar(): array
    {
        return $this->repositorio->listar();
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
