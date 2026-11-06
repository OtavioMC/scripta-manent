<?php

declare(strict_types=1);

namespace ScriptaManent\Application\Livro;

use ScriptaManent\Domain\ErroDeValidacao;
use ScriptaManent\Domain\Livro\Livro;

final readonly class CadastroDeLivros
{
    public function __construct(private RepositorioDeLivros $repositorio)
    {
    }

    /**
     * @return list<LivroNaLista>
     */
    public function listar(): array
    {
        return $this->repositorio->listar();
    }

    public function buscar(int $codigo): ?Livro
    {
        return $this->repositorio->buscar($codigo);
    }

    /**
     * @param array{titulo: string, editora: string, edicao: string, ano: string, valor: string} $campos
     * @param list<int> $autores
     * @param list<int> $assuntos
     * @throws ErroDeValidacao
     */
    public function salvar(?int $codigo, array $campos, array $autores, array $assuntos): Livro
    {
        $livro = Livro::criar(
            $codigo,
            $campos['titulo'],
            $campos['editora'],
            $campos['edicao'],
            $campos['ano'],
            $campos['valor'],
            $autores,
            $assuntos,
        );

        if ($codigo === null) {
            return $this->repositorio->inserir($livro);
        }

        $this->repositorio->alterar($livro);

        return $livro;
    }

    public function excluir(int $codigo): void
    {
        $this->repositorio->excluir($codigo);
    }
}
