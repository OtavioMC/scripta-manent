<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Integracao;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Application\Busca;
use ScriptaManent\Application\Pagina;
use ScriptaManent\Domain\Autor\Autor;
use ScriptaManent\Domain\ExclusaoBloqueada;
use ScriptaManent\Infrastructure\Banco\Conexao;
use ScriptaManent\Infrastructure\Banco\RepositorioDeAutoresPdo;

final class RepositorioDeAutoresPdoTest extends TestCase
{
    private RepositorioDeAutoresPdo $repositorio;

    protected function setUp(): void
    {
        ConexaoDeTeste::iniciarTransacao();
        $this->repositorio = new RepositorioDeAutoresPdo(new Conexao(ConexaoDeTeste::pdo(...)));
    }

    protected function tearDown(): void
    {
        ConexaoDeTeste::desfazerTransacao();
    }

    public function testInsereEDevolveOAutorComOCodigoGerado(): void
    {
        $autor = $this->repositorio->inserir(Autor::novo('Rubem Fonseca'));

        self::assertNotNull($autor->codigo);
        self::assertSame('Rubem Fonseca', $autor->nome);
    }

    public function testBuscaOAutorPeloCodigo(): void
    {
        $inserido = $this->repositorio->inserir(Autor::novo('Rubem Fonseca'));

        self::assertEquals($inserido, $this->repositorio->buscar((int) $inserido->codigo));
    }

    public function testBuscaDevolveNuloQuandoOCodigoNaoExiste(): void
    {
        self::assertNull($this->repositorio->buscar(999999));
    }

    public function testListaOsAutoresEmOrdemAlfabetica(): void
    {
        $this->repositorio->inserir(Autor::novo('Zélia Gattai'));
        $this->repositorio->inserir(Autor::novo('Adélia Prado'));

        $nomes = array_map(static fn (Autor $autor): string => $autor->nome, $this->repositorio->listar());

        self::assertLessThan(array_search('Zélia Gattai', $nomes, true), array_search('Adélia Prado', $nomes, true));
    }

    public function testBuscaPeloNomeSemDiferenciarAcentoNemMaiuscula(): void
    {
        $this->repositorio->inserir(Autor::novo('Otília Brandão'));

        $nomes = array_map(
            static fn (Autor $autor): string => $autor->nome,
            $this->repositorio->listar(Busca::deTexto('otilia brandao')),
        );

        self::assertSame(['Otília Brandão'], $nomes);
    }

    public function testCuringaDigitadoNaBuscaNaoTrazTodosOsAutores(): void
    {
        self::assertSame([], $this->repositorio->listar(Busca::deTexto('%')));
    }

    public function testAlteraONomeDoAutor(): void
    {
        $inserido = $this->repositorio->inserir(Autor::novo('Rubem Fonsec'));

        $this->repositorio->alterar(Autor::existente((int) $inserido->codigo, 'Rubem Fonseca'));

        self::assertSame('Rubem Fonseca', $this->repositorio->buscar((int) $inserido->codigo)?->nome);
    }

    public function testExcluiAutorSemLivros(): void
    {
        $inserido = $this->repositorio->inserir(Autor::novo('Rubem Fonseca'));

        $this->repositorio->excluir((int) $inserido->codigo);

        self::assertNull($this->repositorio->buscar((int) $inserido->codigo));
    }

    public function testExclusaoDeAutorComLivrosEBloqueadaComOsTitulosEmOrdem(): void
    {
        $autor = (int) $this->repositorio->inserir(Autor::novo('Rubem Fonseca'))->codigo;
        $this->vincular($this->livro('Feliz Ano Novo'), $autor);
        $this->vincular($this->livro('Agosto'), $autor);

        try {
            $this->repositorio->excluir($autor);
            self::fail('A exclusão de autor com livros não foi bloqueada.');
        } catch (ExclusaoBloqueada $bloqueio) {
            self::assertSame(
                'O autor Rubem Fonseca está vinculado a 2 livros. Remova os vínculos antes de excluir.',
                $bloqueio->getMessage(),
            );
            self::assertSame(['Agosto', 'Feliz Ano Novo'], $bloqueio->titulos);
        }
    }

    public function testMensagemDeBloqueioUsaSingularParaUmLivro(): void
    {
        $autor = (int) $this->repositorio->inserir(Autor::novo('Rubem Fonseca'))->codigo;
        $this->vincular($this->livro('Agosto'), $autor);

        $this->expectExceptionMessage('O autor Rubem Fonseca está vinculado a 1 livro.');

        $this->repositorio->excluir($autor);
    }

    public function testExclusaoBloqueadaMantemOAutor(): void
    {
        $autor = (int) $this->repositorio->inserir(Autor::novo('Rubem Fonseca'))->codigo;
        $this->vincular($this->livro('Agosto'), $autor);

        try {
            $this->repositorio->excluir($autor);
        } catch (ExclusaoBloqueada) {
        }

        self::assertNotNull($this->repositorio->buscar($autor));
    }

    public function testSegundaPaginaTrazOsAutoresDepoisDaPrimeiraPaginaCheia(): void
    {
        $this->inserirAutoresNumerados(Pagina::ITENS + 5);

        $lista = $this->repositorio->pagina(Busca::deTexto('zéfiro'), Pagina::pedidaNaUrl('2'));

        self::assertSame(Pagina::ITENS + 5, $lista->totalDeItens);
        self::assertSame(
            ['Zéfiro 21', 'Zéfiro 22', 'Zéfiro 23', 'Zéfiro 24', 'Zéfiro 25'],
            array_map(static fn (Autor $autor): string => $autor->nome, $lista->itens),
        );
    }

    public function testPrimeiraPaginaTrazUmaPaginaCheiaEmOrdemDeNome(): void
    {
        $this->inserirAutoresNumerados(Pagina::ITENS + 5);

        $lista = $this->repositorio->pagina(Busca::deTexto('zéfiro'), Pagina::pedidaNaUrl('1'));

        self::assertCount(Pagina::ITENS, $lista->itens);
        self::assertSame('Zéfiro 01', $lista->itens[0]->nome ?? null);
    }

    public function testPaginaAlemDaUltimaTrazAUltima(): void
    {
        $this->inserirAutoresNumerados(Pagina::ITENS + 5);

        $lista = $this->repositorio->pagina(Busca::deTexto('zéfiro'), Pagina::pedidaNaUrl('9'));

        self::assertSame(2, $lista->pagina->numero);
        self::assertCount(5, $lista->itens);
    }

    public function testExcluirOUnicoAutorDaSegundaPaginaVoltaParaAPrimeira(): void
    {
        $codigos = $this->inserirAutoresNumerados(Pagina::ITENS + 1);
        $this->repositorio->excluir($codigos[Pagina::ITENS]);

        $lista = $this->repositorio->pagina(Busca::deTexto('zéfiro'), Pagina::pedidaNaUrl('2'));

        self::assertSame(1, $lista->pagina->numero);
        self::assertSame(Pagina::ITENS, $lista->totalDeItens);
        self::assertCount(Pagina::ITENS, $lista->itens);
    }

    public function testBuscaSemResultadoTrazPaginaVazia(): void
    {
        $lista = $this->repositorio->pagina(Busca::deTexto('nenhum autor tem este nome'), Pagina::pedidaNaUrl('3'));

        self::assertSame(0, $lista->totalDeItens);
        self::assertSame([], $lista->itens);
        self::assertSame(1, $lista->pagina->numero);
    }

    /**
     * @return list<int>
     */
    private function inserirAutoresNumerados(int $quantidade): array
    {
        $codigos = [];
        for ($numero = 1; $numero <= $quantidade; $numero++) {
            $codigos[] = (int) $this->repositorio->inserir(Autor::novo(sprintf('Zéfiro %02d', $numero)))->codigo;
        }

        return $codigos;
    }

    private function livro(string $titulo): int
    {
        return ConexaoDeTeste::inserir(
            'INSERT INTO Livro (Titulo, Editora, Edicao, AnoPublicacao, Valor)
             VALUES (:titulo, :editora, 1, :ano, :valor)',
            ['titulo' => $titulo, 'editora' => 'Agir', 'ano' => '1990', 'valor' => '45.00'],
        );
    }

    private function vincular(int $livro, int $autor): void
    {
        ConexaoDeTeste::inserir(
            'INSERT INTO Livro_Autor (Livro_Codl, Autor_CodAu) VALUES (:livro, :autor)',
            ['livro' => $livro, 'autor' => $autor],
        );
    }
}
