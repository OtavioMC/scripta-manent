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

    public function listar(?Busca $busca = null): array
    {
        return $this->cache->lembrar(
            'livros',
            $busca->texto ?? '',
            fn (): array => $this->repositorio->listar($busca),
            static fn (array $livros): array => array_map(static fn (LivroNaLista $livro): array => [
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
            ], $livros),
            static fn (mixed $dados): array => array_map(static function (mixed $item): LivroNaLista {
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
            }, Dados::lista($dados)),
        );
    }

    public function pagina(Busca $busca, Pagina $pagina): ListaPaginada
    {
        return $this->repositorio->pagina($busca, $pagina);
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
}
