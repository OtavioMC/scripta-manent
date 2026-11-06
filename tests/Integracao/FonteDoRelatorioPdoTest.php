<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Integracao;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Domain\Relatorio\LinhaDoRelatorio;
use ScriptaManent\Infrastructure\Banco\Conexao;
use ScriptaManent\Infrastructure\Banco\FonteDoRelatorioPdo;

final class FonteDoRelatorioPdoTest extends TestCase
{
    protected function setUp(): void
    {
        ConexaoDeTeste::iniciarTransacao();
    }

    protected function tearDown(): void
    {
        ConexaoDeTeste::desfazerTransacao();
    }

    public function testLeAsLinhasDaViewComOsDadosDoLivroEDoAutor(): void
    {
        $livro = $this->livro('Manual do Pequeno Jardim', '1234.56');
        $autor = ConexaoDeTeste::inserir('INSERT INTO Autor (Nome) VALUES (:nome)', ['nome' => 'Otília Brandão']);
        ConexaoDeTeste::inserir(
            'INSERT INTO Livro_Autor (Livro_Codl, Autor_CodAu) VALUES (:livro, :autor)',
            ['livro' => $livro, 'autor' => $autor],
        );

        $linha = $this->linhaDoLivro($livro);

        self::assertSame($autor, $linha->codigoDoAutor);
        self::assertSame('Otília Brandão', $linha->autor);
        self::assertSame('Manual do Pequeno Jardim', $linha->titulo);
        self::assertSame(123456, $linha->valor->centavos);
        self::assertSame('', $linha->assuntos);
    }

    public function testLivroSemAutorVemComAutorNulo(): void
    {
        $linha = $this->linhaDoLivro($this->livro('Cantigas do Vale', '20.00'));

        self::assertNull($linha->codigoDoAutor);
        self::assertNull($linha->autor);
    }

    private function linhaDoLivro(int $livro): LinhaDoRelatorio
    {
        $linhas = array_values(array_filter(
            (new FonteDoRelatorioPdo(new Conexao(ConexaoDeTeste::pdo(...))))->linhas(),
            static fn (LinhaDoRelatorio $linha): bool => $linha->codigoDoLivro === $livro,
        ));
        self::assertCount(1, $linhas);

        return $linhas[0];
    }

    private function livro(string $titulo, string $valor): int
    {
        return ConexaoDeTeste::inserir(
            'INSERT INTO Livro (Titulo, Editora, Edicao, AnoPublicacao, Valor)
             VALUES (:titulo, :editora, 1, :ano, :valor)',
            ['titulo' => $titulo, 'editora' => 'Horizonte', 'ano' => '2021', 'valor' => $valor],
        );
    }
}
