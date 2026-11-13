<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Cache;

use ScriptaManent\Application\Busca;
use ScriptaManent\Application\ListaPaginada;
use ScriptaManent\Application\Livro\LivroNaLista;
use ScriptaManent\Application\Livro\RepositorioDeLivros;
use ScriptaManent\Application\Pagina;
use ScriptaManent\Domain\Livro\Dinheiro;
use ScriptaManent\Domain\Livro\Livro;

final readonly class RepositorioDeLivrosEmCache implements RepositorioDeLivros
{
    public function __construct(
        private RepositorioDeLivros $repositorio,
        private LeituraEmCache $cache,
    ) {
    }

    public function pagina(Busca $busca, Pagina $pagina): ListaPaginada
    {
        return $this->cache->lembrar(
            'livros',
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

    public function buscar(int $codigo): ?Livro
    {
        return $this->repositorio->buscar($codigo);
    }

    public function inserir(Livro $livro): Livro
    {
        $inserido = $this->repositorio->inserir($livro);
        $this->cache->invalidar('livros', 'relatorio');

        return $inserido;
    }

    public function alterar(Livro $livro): void
    {
        $this->repositorio->alterar($livro);
        $this->cache->invalidar('livros', 'relatorio');
    }

    public function excluir(int $codigo): void
    {
        $this->repositorio->excluir($codigo);
        $this->cache->invalidar('livros', 'relatorio');
    }

    /**
     * @return array<string, mixed>
     */
    private static function paraDados(LivroNaLista $livro): array
    {
        return [
            'codigo' => $livro->codigo,
            'titulo' => $livro->titulo,
            'editora' => $livro->editora,
            'edicao' => $livro->edicao,
            'ano' => $livro->ano,
            'centavos' => $livro->valor->centavos,
            'autores' => $livro->autores,
            'assuntos' => $livro->assuntos,
            'codigosDosAutores' => $livro->codigosDosAutores,
            'codigosDosAssuntos' => $livro->codigosDosAssuntos,
        ];
    }

    private static function deDados(mixed $item): LivroNaLista
    {
        $registro = Dados::registro($item);

        return new LivroNaLista(
            Dados::inteiro($registro, 'codigo'),
            Dados::texto($registro, 'titulo'),
            Dados::texto($registro, 'editora'),
            Dados::inteiro($registro, 'edicao'),
            Dados::texto($registro, 'ano'),
            Dinheiro::deCentavos(Dados::inteiro($registro, 'centavos')),
            Dados::textos($registro, 'autores'),
            Dados::textos($registro, 'assuntos'),
            Dados::inteiros($registro, 'codigosDosAutores'),
            Dados::inteiros($registro, 'codigosDosAssuntos'),
        );
    }
}
