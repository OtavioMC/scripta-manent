<?php

declare(strict_types=1);

namespace ScriptaManent\Domain\Relatorio;

use ScriptaManent\Domain\Livro\Dinheiro;

final readonly class Relatorio
{
    private const array SEM_ACENTO = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ];

    /**
     * @param list<GrupoDeLivros> $porAutor
     * @param list<GrupoDeLivros> $coautorias
     */
    private function __construct(
        public array $porAutor,
        public ?GrupoDeLivros $semAutor,
        public int $totalDeLivros,
        public Dinheiro $valorTotal,
        public array $coautorias,
    ) {
    }

    /**
     * @param list<LinhaDoRelatorio> $linhas
     */
    public static function montar(array $linhas): self
    {
        $porAutor = [];
        $semAutor = [];
        $livros = [];
        $autoresDoLivro = [];

        foreach ($linhas as $linha) {
            $livro = LivroDoRelatorio::daLinha($linha);
            $livros[$linha->codigoDoLivro] = $livro;

            if ($linha->codigoDoAutor === null || $linha->autor === null) {
                $semAutor[] = $livro;
                continue;
            }

            $porAutor[$linha->codigoDoAutor] ??= ['autor' => $linha->autor, 'livros' => []];
            $porAutor[$linha->codigoDoAutor]['livros'][] = $livro;
            $autoresDoLivro[$linha->codigoDoLivro][] = $linha->autor;
        }

        return new self(
            array_values(array_map(
                static fn (array $grupo): GrupoDeLivros => new GrupoDeLivros([$grupo['autor']], $grupo['livros']),
                $porAutor,
            )),
            $semAutor === [] ? null : new GrupoDeLivros([], $semAutor),
            count($livros),
            self::somar(array_values($livros)),
            self::coautorias($autoresDoLivro, $livros),
        );
    }

    /**
     * @param list<LivroDoRelatorio> $livros
     */
    public static function somar(array $livros): Dinheiro
    {
        return array_reduce(
            $livros,
            static fn (Dinheiro $total, LivroDoRelatorio $livro): Dinheiro => $total->mais($livro->valor),
            Dinheiro::zero(),
        );
    }

    /**
     * @param array<int, list<string>> $autoresDoLivro
     * @param array<int, LivroDoRelatorio> $livros
     * @return list<GrupoDeLivros>
     */
    private static function coautorias(array $autoresDoLivro, array $livros): array
    {
        $conjuntos = [];
        foreach ($autoresDoLivro as $codigo => $autores) {
            if (count($autores) < 2) {
                continue;
            }

            usort($autores, self::compararNomes(...));
            $chave = implode("\n", $autores);
            $conjuntos[$chave] ??= ['autores' => $autores, 'livros' => []];
            $conjuntos[$chave]['livros'][] = $livros[$codigo];
        }

        $grupos = array_values(array_map(
            static fn (array $conjunto): GrupoDeLivros => new GrupoDeLivros($conjunto['autores'], $conjunto['livros']),
            $conjuntos,
        ));
        usort($grupos, static fn (GrupoDeLivros $a, GrupoDeLivros $b): int => self::compararNomes(
            $a->titulo(),
            $b->titulo(),
        ));

        return $grupos;
    }

    private static function compararNomes(string $a, string $b): int
    {
        return strcmp(strtr(mb_strtolower($a), self::SEM_ACENTO), strtr(mb_strtolower($b), self::SEM_ACENTO));
    }
}
