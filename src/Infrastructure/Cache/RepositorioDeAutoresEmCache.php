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
            'lista:' . ($busca->texto ?? ''),
            fn (): array => $this->repositorio->listar($busca),
            static fn (array $autores): array => array_map(self::paraDados(...), $autores),
            static fn (mixed $dados): array => array_map(self::deDados(...), Dados::lista($dados)),
        );
    }

    public function pagina(Busca $busca, Pagina $pagina): ListaPaginada
    {
        return $this->cache->lembrar(
            'autores',
            sprintf('pagina:%d:%s', $pagina->numero, $busca->texto),
            fn (): ListaPaginada => $this->repositorio->pagina($busca, $pagina),
            static fn (ListaPaginada $lista): array => [
                'itens' => array_map(self::paraDados(...), $lista->itens),
                'totalDeItens' => $lista->totalDeItens,
                'pagina' => $lista->pagina->numero,
            ],
            static function (mixed $dados): ListaPaginada {
                $registro = Dados::registro($dados);

                return new ListaPaginada(
                    array_map(self::deDados(...), Dados::lista($registro['itens'] ?? null)),
                    Dados::inteiro($registro, 'totalDeItens'),
                    Pagina::pedidaNaUrl((string) Dados::inteiro($registro, 'pagina')),
                );
            },
        );
    }

    /**
     * @return array{codigo: int|null, nome: string}
     */
    private static function paraDados(Autor $autor): array
    {
        return ['codigo' => $autor->codigo, 'nome' => $autor->nome];
    }

    private static function deDados(mixed $item): Autor
    {
        $registro = Dados::registro($item);

        return Autor::existente(Dados::inteiro($registro, 'codigo'), Dados::texto($registro, 'nome'));
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
