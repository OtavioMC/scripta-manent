<?php

declare(strict_types=1);

namespace ScriptaManent\Application\Assunto;

use ScriptaManent\Domain\Assunto\Assunto;
use ScriptaManent\Domain\ErroDeValidacao;
use ScriptaManent\Domain\ExclusaoBloqueada;

final readonly class CadastroDeAssuntos
{
    public function __construct(private RepositorioDeAssuntos $repositorio)
    {
    }

    /**
     * @return list<Assunto>
     */
    public function listar(): array
    {
        return $this->repositorio->listar();
    }

    public function buscar(int $codigo): ?Assunto
    {
        return $this->repositorio->buscar($codigo);
    }

    /**
     * @throws ErroDeValidacao
     */
    public function cadastrar(string $descricao): Assunto
    {
        return $this->repositorio->inserir(Assunto::novo($descricao));
    }

    /**
     * @throws ErroDeValidacao
     */
    public function alterar(int $codigo, string $descricao): Assunto
    {
        $assunto = Assunto::existente($codigo, $descricao);
        $this->repositorio->alterar($assunto);

        return $assunto;
    }

    /**
     * @throws ExclusaoBloqueada
     */
    public function excluir(int $codigo): void
    {
        $this->repositorio->excluir($codigo);
    }
}
