<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Integracao;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Application\Busca;
use ScriptaManent\Application\Livro\LivroNaLista;
use ScriptaManent\Application\Pagina;
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

    public function testSegundaPaginaTrazOsLivrosDepoisDaPrimeiraPaginaCheia(): void
    {
        $this->inserirLivrosNumerados(Pagina::ITENS + 5);

        $lista = $this->repositorio->pagina(Busca::deTexto('zéfiro'), Pagina::pedidaNaUrl('2'));

        self::assertSame(Pagina::ITENS + 5, $lista->totalDeItens);
        self::assertSame(
            ['Zéfiro 21', 'Zéfiro 22', 'Zéfiro 23', 'Zéfiro 24', 'Zéfiro 25'],
            array_map(static fn (LivroNaLista $livro): string => $livro->titulo, $lista->itens),
        );
    }

    public function testPrimeiraPaginaTrazUmaPaginaCheiaEmOrdemDeTitulo(): void
    {
        $this->inserirLivrosNumerados(Pagina::ITENS + 5);

        $lista = $this->repositorio->pagina(Busca::deTexto('zéfiro'), Pagina::pedidaNaUrl('1'));

        self::assertCount(Pagina::ITENS, $lista->itens);
        self::assertSame('Zéfiro 01', $lista->itens[0]->titulo ?? null);
    }

    public function testPaginaAlemDaUltimaTrazAUltima(): void
    {
        $this->inserirLivrosNumerados(Pagina::ITENS + 5);

        $lista = $this->repositorio->pagina(Busca::deTexto('zéfiro'), Pagina::pedidaNaUrl('9'));

        self::assertSame(2, $lista->pagina->numero);
        self::assertCount(5, $lista->itens);
    }

    public function testPaginaTrazOsNomesDosAutoresEAssuntos(): void
    {
        $autor = $this->autor('Otília Brandão');
        $assunto = $this->assunto('Jardinagem');
        $this->repositorio->inserir(
            Livro::criar(null, 'Zéfiro e o Jardim', 'Horizonte', '1', '2020', '10,00', [$autor], [$assunto]),
        );

        $livro = $this->repositorio->pagina(Busca::deTexto('zéfiro'), Pagina::pedidaNaUrl('1'))->itens[0] ?? null;

        self::assertNotNull($livro);
        self::assertSame(['Otília Brandão'], $livro->autores);
        self::assertSame(['Jardinagem'], $livro->assuntos);
    }

    public function testBuscaSemResultadoTrazPaginaVazia(): void
    {
        $lista = $this->repositorio->pagina(Busca::deTexto('nenhum livro tem este texto'), Pagina::pedidaNaUrl('3'));

        self::assertSame(0, $lista->totalDeItens);
        self::assertSame([], $lista->itens);
        self::assertSame(1, $lista->pagina->numero);
    }

    private function inserirLivrosNumerados(int $quantidade): void
    {
        for ($numero = 1; $numero <= $quantidade; $numero++) {
            $this->repositorio->inserir(Livro::criar(
                null,
                sprintf('Zéfiro %02d', $numero),
                'Horizonte',
                '1',
                '2020',
                '10,00',
                [],
                [],
            ));
        }
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
