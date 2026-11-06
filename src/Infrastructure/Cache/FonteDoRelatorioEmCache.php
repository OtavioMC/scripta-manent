<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Cache;

use ScriptaManent\Application\Busca;
use ScriptaManent\Application\Relatorio\FonteDoRelatorio;
use ScriptaManent\Domain\Livro\Dinheiro;
use ScriptaManent\Domain\Relatorio\LinhaDoRelatorio;

final readonly class FonteDoRelatorioEmCache implements FonteDoRelatorio
{
    public function __construct(
        private FonteDoRelatorio $fonte,
        private LeituraEmCache $cache,
    ) {
    }

    public function linhas(?Busca $busca = null): array
    {
        return $this->cache->lembrar(
            'relatorio',
            $busca->texto ?? '',
            fn (): array => $this->fonte->linhas($busca),
            static fn (array $linhas): array => array_map(static fn (LinhaDoRelatorio $linha): array => [
                'codigoDoAutor' => $linha->codigoDoAutor,
                'autor' => $linha->autor,
                'codigoDoLivro' => $linha->codigoDoLivro,
                'titulo' => $linha->titulo,
                'editora' => $linha->editora,
                'edicao' => $linha->edicao,
                'ano' => $linha->ano,
                'centavos' => $linha->valor->centavos,
                'assuntos' => $linha->assuntos,
            ], $linhas),
            static fn (mixed $dados): array => array_map(static function (mixed $item): LinhaDoRelatorio {
                $registro = Dados::registro($item);

                return new LinhaDoRelatorio(
                    Dados::inteiroOuNulo($registro, 'codigoDoAutor'),
                    Dados::textoOuNulo($registro, 'autor'),
                    Dados::inteiro($registro, 'codigoDoLivro'),
                    Dados::texto($registro, 'titulo'),
                    Dados::texto($registro, 'editora'),
                    Dados::inteiro($registro, 'edicao'),
                    Dados::texto($registro, 'ano'),
                    Dinheiro::deCentavos(Dados::inteiro($registro, 'centavos')),
                    Dados::texto($registro, 'assuntos'),
                );
            }, Dados::lista($dados)),
        );
    }
}
