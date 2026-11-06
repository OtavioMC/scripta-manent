<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Integracao;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Application\Busca;
use ScriptaManent\Domain\ErroDeValidacao;
use ScriptaManent\Domain\Livro\Livro;
use ScriptaManent\Infrastructure\Banco\Conexao;
use ScriptaManent\Infrastructure\Banco\RepositorioDeLivrosPdo;

final class RepositorioDeLivrosPdoTest extends TestCase
{
    private RepositorioDeLivrosPdo $repositorio;

    protected function setUp(): void
    {
        ConexaoDeTeste::iniciarTransacao();
        $this->repositorio = new RepositorioDeLivrosPdo(new Conexao(ConexaoDeTeste::pdo(...)));
    }

    protected function tearDown(): void
    {
        ConexaoDeTeste::desfazerTransacao();
    }

    public function testInsereOLivroComAutoresEAssuntosEBuscaDeVolta(): void
    {
        $autores = [$this->autor('Otília Brandão'), $this->autor('Jorge Vasques')];
        $assunto = $this->assunto('Jardinagem');
        sort($autores);

        $inserido = $this->repositorio->inserir(
            Livro::criar(null, 'Manual do Pequeno Jardim', 'Horizonte', '3', '2021', '1.234,56', $autores, [$assunto]),
        );

        self::assertEquals($inserido, $this->repositorio->buscar((int) $inserido->codigo));
        self::assertSame(123456, $inserido->valor->centavos);
    }

    public function testInsereLivroSemAutorESemAssunto(): void
    {
        $inserido = $this->repositorio->inserir(
            Livro::criar(null, 'Cantigas do Vale', 'Horizonte', '1', '1950', '20,00', [], []),
        );

        $buscado = $this->repositorio->buscar((int) $inserido->codigo);
        self::assertNotNull($buscado);
        self::assertSame([], $buscado->autores);
        self::assertSame([], $buscado->assuntos);
    }

    public function testBuscaDevolveNuloQuandoOCodigoNaoExiste(): void
    {
        self::assertNull($this->repositorio->buscar(999999));
    }

    public function testAlteraOsDadosETrocaOsVinculos(): void
    {
        $antigo = $this->autor('Otília Brandão');
        $novo = $this->autor('Jorge Vasques');
        $inserido = $this->repositorio->inserir(
            Livro::criar(null, 'Manual do Jardim', 'Horizonte', '1', '2020', '50,00', [$antigo], []),
        );

        $this->repositorio->alterar(Livro::criar(
            $inserido->codigo,
            'Manual do Pequeno Jardim',
            'Horizonte',
            '2',
            '2021',
            '55,00',
            [$novo],
            [$this->assunto('Jardinagem')],
        ));

        $buscado = $this->repositorio->buscar((int) $inserido->codigo);
        self::assertNotNull($buscado);
        self::assertSame('Manual do Pequeno Jardim', $buscado->titulo);
        self::assertSame(2, $buscado->edicao);
        self::assertSame(5500, $buscado->valor->centavos);
        self::assertSame([$novo], $buscado->autores);
        self::assertCount(1, $buscado->assuntos);
    }

    public function testExcluiOLivroESeusVinculosMantendoOAutor(): void
    {
        $autor = $this->autor('Otília Brandão');
        $inserido = $this->repositorio->inserir(
            Livro::criar(null, 'Manual do Jardim', 'Horizonte', '1', '2020', '50,00', [$autor], []),
        );

        $this->repositorio->excluir((int) $inserido->codigo);

        self::assertNull($this->repositorio->buscar((int) $inserido->codigo));
        self::assertCount(1, ConexaoDeTeste::consultar('SELECT CodAu FROM Autor WHERE CodAu = :autor', [
            'autor' => $autor,
        ]));
    }

    public function testListaTrazOsNomesDosAutoresEAssuntosEmOrdem(): void
    {
        $this->repositorio->inserir(Livro::criar(
            null,
            'Manual do Pequeno Jardim',
            'Horizonte',
            '3',
            '2021',
            '1.234,56',
            [$this->autor('Otília Brandão'), $this->autor('Jorge Vasques')],
            [$this->assunto('Jardinagem'), $this->assunto('Botânica')],
        ));

        $livros = array_values(array_filter(
            $this->repositorio->listar(),
            static fn ($livro): bool => $livro->titulo === 'Manual do Pequeno Jardim',
        ));

        self::assertCount(1, $livros);
        self::assertSame(['Jorge Vasques', 'Otília Brandão'], $livros[0]->autores);
        self::assertSame(['Botânica', 'Jardinagem'], $livros[0]->assuntos);
        self::assertCount(2, $livros[0]->codigosDosAutores);
        self::assertCount(2, $livros[0]->codigosDosAssuntos);
        self::assertSame('R$ 1.234,56', $livros[0]->valor->formatado());
    }

    public function testBuscaEncontraOLivroPeloNomeDoAssunto(): void
    {
        $this->repositorio->inserir(Livro::criar(
            null,
            'Manual do Pequeno Jardim',
            'Horizonte',
            '1',
            '2021',
            '50,00',
            [],
            [$this->assunto('Jardinagem Urbana')],
        ));

        $titulos = array_map(
            static fn ($livro): string => $livro->titulo,
            $this->repositorio->listar(Busca::deTexto('jardinagem urbana')),
        );

        self::assertSame(['Manual do Pequeno Jardim'], $titulos);
    }

    public function testVinculoComAutorQueNaoExisteViraErroDeValidacao(): void
    {
        $this->expectException(ErroDeValidacao::class);

        $this->repositorio->inserir(
            Livro::criar(null, 'Manual do Jardim', 'Horizonte', '1', '2020', '50,00', [999999], []),
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
}
