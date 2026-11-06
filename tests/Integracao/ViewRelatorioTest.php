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
