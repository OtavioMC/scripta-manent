<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Domain\Relatorio;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Domain\Livro\Dinheiro;
use ScriptaManent\Domain\Relatorio\GrupoDeLivros;
use ScriptaManent\Domain\Relatorio\LinhaDoRelatorio;
use ScriptaManent\Domain\Relatorio\LivroDoRelatorio;
use ScriptaManent\Domain\Relatorio\Relatorio;

final class RelatorioTest extends TestCase
{
    public function testAgrupaOsLivrosPorAutorNaOrdemDasLinhas(): void
    {
        $relatorio = Relatorio::montar([
            $this->linha(1, 'Clarice Lispector', 10, 'A Hora da Estrela', '39,90'),
            $this->linha(1, 'Clarice Lispector', 11, 'Laços de Família', '44,90'),
            $this->linha(2, 'Machado de Assis', 12, 'Dom Casmurro', '34,90'),
        ]);

        self::assertSame(['Clarice Lispector', 'Machado de Assis'], $this->titulos($relatorio->porAutor));
        self::assertSame(['A Hora da Estrela', 'Laços de Família'], $this->livros($relatorio->porAutor[0]));
    }

    public function testLivroComDoisAutoresApareceNoGrupoDeCadaUm(): void
    {
        $relatorio = Relatorio::montar([
            $this->linha(5, 'Neil Gaiman', 20, 'Belas Maldições', '59,90'),
            $this->linha(6, 'Terry Pratchett', 20, 'Belas Maldições', '59,90'),
        ]);

        self::assertSame(['Belas Maldições'], $this->livros($relatorio->porAutor[0]));
        self::assertSame(['Belas Maldições'], $this->livros($relatorio->porAutor[1]));
    }

    public function testLivroSemAutorFicaNumGrupoProprioForaDosAutores(): void
    {
        $relatorio = Relatorio::montar([
            $this->linha(null, null, 30, 'As Mil e Uma Noites', '79,90'),
            $this->linha(2, 'Machado de Assis', 12, 'Dom Casmurro', '34,90'),
        ]);

        self::assertSame(['Machado de Assis'], $this->titulos($relatorio->porAutor));
        self::assertNotNull($relatorio->semAutor);
        self::assertSame(['As Mil e Uma Noites'], $this->livros($relatorio->semAutor));
    }

    public function testSemLivroSemAutorOGrupoProprioNaoExiste(): void
    {
        $relatorio = Relatorio::montar([$this->linha(2, 'Machado de Assis', 12, 'Dom Casmurro', '34,90')]);

        self::assertNull($relatorio->semAutor);
    }

    public function testCadaGrupoTemQuantidadeEValorDosSeusLivros(): void
    {
        $relatorio = Relatorio::montar([
            $this->linha(1, 'Clarice Lispector', 10, 'A Hora da Estrela', '39,90'),
            $this->linha(1, 'Clarice Lispector', 11, 'Laços de Família', '44,90'),
        ]);

        self::assertSame(2, $relatorio->porAutor[0]->quantidade());
        self::assertSame(8480, $relatorio->porAutor[0]->valor()->centavos);
    }

    public function testTotalGeralContaCadaLivroUmaVezSo(): void
    {
        $relatorio = Relatorio::montar([
            $this->linha(5, 'Neil Gaiman', 20, 'Belas Maldições', '59,90'),
            $this->linha(5, 'Neil Gaiman', 21, 'Coraline', '39,90'),
            $this->linha(6, 'Terry Pratchett', 20, 'Belas Maldições', '59,90'),
            $this->linha(null, null, 30, 'As Mil e Uma Noites', '79,90'),
        ]);

        self::assertSame(3, $relatorio->totalDeLivros);
        self::assertSame(17970, $relatorio->valorTotal->centavos);
    }

    public function testCoautoriasAgrupamPeloConjuntoDeAutoresComOsNomesJuntos(): void
    {
        $relatorio = Relatorio::montar([
            $this->linha(7, 'Steven D. Levitt', 40, 'Freakonomics', '64,00'),
            $this->linha(8, 'Stephen J. Dubner', 40, 'Freakonomics', '64,00'),
            $this->linha(7, 'Steven D. Levitt', 41, 'SuperFreakonomics', '69,00'),
            $this->linha(8, 'Stephen J. Dubner', 41, 'SuperFreakonomics', '69,00'),
            $this->linha(5, 'Neil Gaiman', 20, 'Belas Maldições', '59,90'),
            $this->linha(6, 'Terry Pratchett', 20, 'Belas Maldições', '59,90'),
            $this->linha(5, 'Neil Gaiman', 21, 'Coraline', '39,90'),
        ]);

        self::assertSame(
            ['Neil Gaiman e Terry Pratchett', 'Stephen J. Dubner e Steven D. Levitt'],
            $this->titulos($relatorio->coautorias),
        );
        self::assertSame(['Freakonomics', 'SuperFreakonomics'], $this->livros($relatorio->coautorias[1]));
        self::assertSame(2, $relatorio->coautorias[1]->quantidade());
        self::assertSame(13300, $relatorio->coautorias[1]->valor()->centavos);
    }

    public function testCoautoriaComTresOuMaisAutoresUsaVirgulaEe(): void
    {
        $relatorio = Relatorio::montar([
            $this->linha(9, 'Erich Gamma', 50, 'Padrões de Projeto', '189,00'),
            $this->linha(10, 'Richard Helm', 50, 'Padrões de Projeto', '189,00'),
            $this->linha(11, 'Ralph Johnson', 50, 'Padrões de Projeto', '189,00'),
        ]);

        self::assertSame(['Erich Gamma, Ralph Johnson e Richard Helm'], $this->titulos($relatorio->coautorias));
        self::assertSame(1, $relatorio->coautorias[0]->quantidade());
    }

    public function testLivroComUmAutorNaoEntraNasCoautorias(): void
    {
        self::assertSame([], Relatorio::montar([
            $this->linha(5, 'Neil Gaiman', 21, 'Coraline', '39,90'),
        ])->coautorias);
    }

    public function testRelatorioSemLivrosFicaVazioComTotaisZerados(): void
    {
        $relatorio = Relatorio::montar([]);

        self::assertSame([], $relatorio->porAutor);
        self::assertNull($relatorio->semAutor);
        self::assertSame(0, $relatorio->totalDeLivros);
        self::assertSame(0, $relatorio->valorTotal->centavos);
    }

    private function linha(
        ?int $codigoDoAutor,
        ?string $autor,
        int $livro,
        string $titulo,
        string $valor,
    ): LinhaDoRelatorio {
        return new LinhaDoRelatorio(
            $codigoDoAutor,
            $autor,
            $livro,
            $titulo,
            'Editora',
            1,
            '2000',
            Dinheiro::deTexto($valor),
            'Romance',
        );
    }

    /**
     * @param list<GrupoDeLivros> $grupos
     * @return list<string>
     */
    private function titulos(array $grupos): array
    {
        return array_map(static fn (GrupoDeLivros $grupo): string => $grupo->titulo(), $grupos);
    }

    /**
     * @return list<string>
     */
    private function livros(GrupoDeLivros $grupo): array
    {
        return array_map(static fn (LivroDoRelatorio $livro): string => $livro->titulo, $grupo->livros);
    }
}
