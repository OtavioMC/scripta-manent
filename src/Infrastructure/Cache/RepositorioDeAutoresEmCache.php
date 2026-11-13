<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Cache;

use ScriptaManent\Application\Autor\RepositorioDeAutores;
use ScriptaManent\Application\Busca;
use ScriptaManent\Application\ListaPaginada;
use ScriptaManent\Application\Pagina;
use ScriptaManent\Domain\Autor\Autor;

final readonly class RepositorioDeAutoresEmCache implements RepositorioDeAutores
{
    public function __construct(
        private RepositorioDeAutores $repositorio,
        private LeituraEmCache $cache,
    ) {
    }

    public function listar(?Busca $busca = null): array
    {
        return $this->cache->lembrar(
            'autores',
            $busca->texto ?? '',
            fn (): array => $this->repositorio->listar($busca),
            static fn (array $autores): array => array_map(
                static fn (Autor $autor): array => ['codigo' => $autor->codigo, 'nome' => $autor->nome],
                $autores,
            ),
            static fn (mixed $dados): array => array_map(static function (mixed $item): Autor {
                $registro = Dados::registro($item);

                return Autor::existente(Dados::inteiro($registro, 'codigo'), Dados::texto($registro, 'nome'));
            }, Dados::lista($dados)),
        );
    }

    public function pagina(Busca $busca, Pagina $pagina): ListaPaginada
    {
        return $this->repositorio->pagina($busca, $pagina);
    }

    public function buscar(int $codigo): ?Autor
    {
        return $this->repositorio->buscar($codigo);
    }

    public function inserir(Autor $autor): Autor
    {
        $inserido = $this->repositorio->inserir($autor);
        $this->cache->invalidar('autores', 'livros', 'relatorio');

        return $inserido;
    }

    public function alterar(Autor $autor): void
    {
        $this->repositorio->alterar($autor);
        $this->cache->invalidar('autores', 'livros', 'relatorio');
    }

    public function excluir(int $codigo): void
    {
        $this->repositorio->excluir($codigo);
        $this->cache->invalidar('autores', 'livros', 'relatorio');
    }
}
