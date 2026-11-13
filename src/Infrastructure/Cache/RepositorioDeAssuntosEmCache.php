<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Cache;

use ScriptaManent\Application\Assunto\RepositorioDeAssuntos;
use ScriptaManent\Application\Busca;
use ScriptaManent\Application\ListaPaginada;
use ScriptaManent\Application\Pagina;
use ScriptaManent\Domain\Assunto\Assunto;

final readonly class RepositorioDeAssuntosEmCache implements RepositorioDeAssuntos
{
    public function __construct(
        private RepositorioDeAssuntos $repositorio,
        private LeituraEmCache $cache,
    ) {
    }

    public function listar(?Busca $busca = null): array
    {
        return $this->cache->lembrar(
            'assuntos',
            'lista:' . ($busca->texto ?? ''),
            fn (): array => $this->repositorio->listar($busca),
            static fn (array $assuntos): array => array_map(self::paraDados(...), $assuntos),
            static fn (mixed $dados): array => array_map(self::deDados(...), Dados::lista($dados)),
        );
    }

    public function pagina(Busca $busca, Pagina $pagina): ListaPaginada
    {
        return $this->cache->lembrar(
            'assuntos',
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
     * @return array{codigo: int|null, descricao: string}
     */
    private static function paraDados(Assunto $assunto): array
    {
        return ['codigo' => $assunto->codigo, 'descricao' => $assunto->descricao];
    }

    private static function deDados(mixed $item): Assunto
    {
        $registro = Dados::registro($item);

        return Assunto::existente(Dados::inteiro($registro, 'codigo'), Dados::texto($registro, 'descricao'));
    }

    public function buscar(int $codigo): ?Assunto
    {
        return $this->repositorio->buscar($codigo);
    }

    public function inserir(Assunto $assunto): Assunto
    {
        $inserido = $this->repositorio->inserir($assunto);
        $this->cache->invalidar('assuntos', 'livros', 'relatorio');

        return $inserido;
    }

    public function alterar(Assunto $assunto): void
    {
        $this->repositorio->alterar($assunto);
        $this->cache->invalidar('assuntos', 'livros', 'relatorio');
    }

    public function excluir(int $codigo): void
    {
        $this->repositorio->excluir($codigo);
        $this->cache->invalidar('assuntos', 'livros', 'relatorio');
    }
}
