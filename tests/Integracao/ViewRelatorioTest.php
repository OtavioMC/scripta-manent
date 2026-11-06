<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Integracao;

use PHPUnit\Framework\TestCase;

final class ViewRelatorioTest extends TestCase
{
    protected function setUp(): void
    {
        ConexaoDeTeste::iniciarTransacao();
    }

    protected function tearDown(): void
    {
        ConexaoDeTeste::desfazerTransacao();
    }

    public function testViewDoRelatorioExiste(): void
    {
        $views = ConexaoDeTeste::consultar(
            "SELECT TABLE_NAME FROM information_schema.VIEWS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vw_livros_por_autor'",
        );

        self::assertCount(1, $views);
    }

    public function testLivroComUmAutorGeraUmaLinhaComOsDadosDoLivroEDoAutor(): void
    {
        $livro = $this->livro('Dom Casmurro');
        $autor = $this->autor('Machado de Assis');
        $this->vincularAutor($livro, $autor);
        $this->vincularAssunto($livro, $this->assunto('Romance'));

        $linhas = ConexaoDeTeste::consultar('SELECT * FROM vw_livros_por_autor WHERE Codl = :livro', [
            'livro' => $livro,
        ]);

        self::assertSame([[
            'CodAu' => (string) $autor,
            'Autor' => 'Machado de Assis',
            'Codl' => (string) $livro,
            'Titulo' => 'Dom Casmurro',
            'Editora' => 'Companhia das Letras',
            'Edicao' => '2',
            'AnoPublicacao' => '2016',
            'Valor' => '39.90',
            'Assuntos' => 'Romance',
        ]], $linhas);
    }

    public function testLivroComDoisAutoresApareceUmaVezParaCadaAutor(): void
    {
        $livro = $this->livro('Belas Maldições');
        $this->vincularAutor($livro, $this->autor('Neil Gaiman'));
        $this->vincularAutor($livro, $this->autor('Terry Pratchett'));

        self::assertSame(
            ['Neil Gaiman | Belas Maldições', 'Terry Pratchett | Belas Maldições'],
            $this->autorETitulo($livro),
        );
    }

    public function testAssuntosDoLivroFicamNumaColunaSoEmOrdemAlfabetica(): void
    {
        $livro = $this->livro('O Hobbit');
        $this->vincularAutor($livro, $this->autor('J. R. R. Tolkien'));
        $this->vincularAssunto($livro, $this->assunto('Romance'));
        $this->vincularAssunto($livro, $this->assunto('Fantasia'));
        $this->vincularAssunto($livro, $this->assunto('Aventura'));

        $linhas = ConexaoDeTeste::consultar('SELECT Assuntos FROM vw_livros_por_autor WHERE Codl = :livro', [
            'livro' => $livro,
        ]);

        self::assertSame([['Assuntos' => 'Aventura, Fantasia, Romance']], $linhas);
    }

    public function testLivroSemAutorApareceComAutorVazio(): void
    {
        $livro = $this->livro('Cantigas do Vale');

        $linhas = ConexaoDeTeste::consultar(
            'SELECT CodAu, Autor, Titulo FROM vw_livros_por_autor WHERE Codl = :livro',
            ['livro' => $livro],
        );

        self::assertSame([['CodAu' => null, 'Autor' => null, 'Titulo' => 'Cantigas do Vale']], $linhas);
    }

    public function testLivroSemAssuntoApareceComAssuntosVazios(): void
    {
        $livro = $this->livro('Memórias Póstumas');
        $this->vincularAutor($livro, $this->autor('Machado de Assis'));

        $linhas = ConexaoDeTeste::consultar('SELECT Assuntos FROM vw_livros_por_autor WHERE Codl = :livro', [
            'livro' => $livro,
        ]);

        self::assertSame([['Assuntos' => null]], $linhas);
    }

    public function testVariosAssuntosNaoMultiplicamAsLinhasDeVariosAutores(): void
    {
        $livro = $this->livro('Manual do Pequeno Jardim');
        $this->vincularAutor($livro, $this->autor('Otília Brandão'));
        $this->vincularAutor($livro, $this->autor('Jorge Vasques'));
        $this->vincularAssunto($livro, $this->assunto('Botânica'));
        $this->vincularAssunto($livro, $this->assunto('Jardinagem'));

        $linhas = ConexaoDeTeste::consultar(
            'SELECT Autor, Assuntos FROM vw_livros_por_autor WHERE Codl = :livro ORDER BY Autor',
            ['livro' => $livro],
        );

        self::assertSame([
            ['Autor' => 'Jorge Vasques', 'Assuntos' => 'Botânica, Jardinagem'],
            ['Autor' => 'Otília Brandão', 'Assuntos' => 'Botânica, Jardinagem'],
        ], $linhas);
    }

    public function testAutorSemLivrosNaoAparece(): void
    {
        $autor = $this->autor('Beatriz Lacerda');

        $linhas = ConexaoDeTeste::consultar('SELECT Codl FROM vw_livros_por_autor WHERE CodAu = :autor', [
            'autor' => $autor,
        ]);

        self::assertSame([], $linhas);
    }

    private function livro(string $titulo): int
    {
        return ConexaoDeTeste::inserir(
            "INSERT INTO Livro (Titulo, Editora, Edicao, AnoPublicacao, Valor)
             VALUES (:titulo, 'Companhia das Letras', 2, '2016', 39.90)",
            ['titulo' => $titulo],
        );
    }

    private function autor(string $nome): int
    {
        return ConexaoDeTeste::inserir('INSERT INTO Autor (Nome) VALUES (:nome)', ['nome' => $nome]);
    }

    private function assunto(string $descricao): int
    {
        return ConexaoDeTeste::inserir('INSERT INTO Assunto (Descricao) VALUES (:descricao)', [
            'descricao' => $descricao,
        ]);
    }

    private function vincularAutor(int $livro, int $autor): void
    {
        ConexaoDeTeste::inserir('INSERT INTO Livro_Autor (Livro_Codl, Autor_CodAu) VALUES (:livro, :autor)', [
            'livro' => $livro,
            'autor' => $autor,
        ]);
    }

    private function vincularAssunto(int $livro, int $assunto): void
    {
        ConexaoDeTeste::inserir(
            'INSERT INTO Livro_Assunto (Livro_Codl, Assunto_codAs) VALUES (:livro, :assunto)',
            ['livro' => $livro, 'assunto' => $assunto],
        );
    }

    /**
     * @return list<string>
     */
    private function autorETitulo(int $livro): array
    {
        $linhas = ConexaoDeTeste::consultar(
            'SELECT Autor, Titulo FROM vw_livros_por_autor WHERE Codl = :livro ORDER BY Autor',
            ['livro' => $livro],
        );

        return array_map(
            static fn (array $linha): string => sprintf('%s | %s', $linha['Autor'] ?? '', $linha['Titulo'] ?? ''),
            $linhas,
        );
    }
}
