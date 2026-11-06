<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Integracao;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Domain\Assunto\Assunto;
use ScriptaManent\Domain\ExclusaoBloqueada;
use ScriptaManent\Infrastructure\Banco\Conexao;
use ScriptaManent\Infrastructure\Banco\RepositorioDeAssuntosPdo;

final class RepositorioDeAssuntosPdoTest extends TestCase
{
    private RepositorioDeAssuntosPdo $repositorio;

    protected function setUp(): void
    {
        ConexaoDeTeste::iniciarTransacao();
        $this->repositorio = new RepositorioDeAssuntosPdo(new Conexao(ConexaoDeTeste::pdo(...)));
    }

    protected function tearDown(): void
    {
        ConexaoDeTeste::desfazerTransacao();
    }

    public function testInsereEDevolveOAssuntoComOCodigoGerado(): void
    {
        $assunto = $this->repositorio->inserir(Assunto::novo('Crônica'));

        self::assertNotNull($assunto->codigo);
        self::assertSame('Crônica', $assunto->descricao);
    }

    public function testBuscaOAssuntoPeloCodigo(): void
    {
        $inserido = $this->repositorio->inserir(Assunto::novo('Crônica'));

        self::assertEquals($inserido, $this->repositorio->buscar((int) $inserido->codigo));
    }

    public function testBuscaDevolveNuloQuandoOCodigoNaoExiste(): void
    {
        self::assertNull($this->repositorio->buscar(999999));
    }

    public function testListaOsAssuntosEmOrdemAlfabetica(): void
    {
        $this->repositorio->inserir(Assunto::novo('Zoologia'));
        $this->repositorio->inserir(Assunto::novo('Arquitetura'));

        $descricoes = array_map(
            static fn (Assunto $assunto): string => $assunto->descricao,
            $this->repositorio->listar(),
        );

        self::assertLessThan(
            array_search('Zoologia', $descricoes, true),
            array_search('Arquitetura', $descricoes, true),
        );
    }

    public function testAlteraADescricaoDoAssunto(): void
    {
        $inserido = $this->repositorio->inserir(Assunto::novo('Cronica'));

        $this->repositorio->alterar(Assunto::existente((int) $inserido->codigo, 'Crônica'));

        self::assertSame('Crônica', $this->repositorio->buscar((int) $inserido->codigo)?->descricao);
    }

    public function testExcluiAssuntoSemLivros(): void
    {
        $inserido = $this->repositorio->inserir(Assunto::novo('Crônica'));

        $this->repositorio->excluir((int) $inserido->codigo);

        self::assertNull($this->repositorio->buscar((int) $inserido->codigo));
    }

    public function testExclusaoDeAssuntoComLivrosEBloqueadaComOsTitulosEmOrdem(): void
    {
        $assunto = (int) $this->repositorio->inserir(Assunto::novo('Crônica'))->codigo;
        $this->vincular($this->livro('Comédias da Vida Privada'), $assunto);
        $this->vincular($this->livro('A Borboleta Amarela'), $assunto);

        try {
            $this->repositorio->excluir($assunto);
            self::fail('A exclusão de assunto com livros não foi bloqueada.');
        } catch (ExclusaoBloqueada $bloqueio) {
            self::assertSame(
                'O assunto Crônica está vinculado a 2 livros. Remova os vínculos antes de excluir.',
                $bloqueio->getMessage(),
            );
            self::assertSame(['A Borboleta Amarela', 'Comédias da Vida Privada'], $bloqueio->titulos);
        }
    }

    public function testExclusaoBloqueadaMantemOAssunto(): void
    {
        $assunto = (int) $this->repositorio->inserir(Assunto::novo('Crônica'))->codigo;
        $this->vincular($this->livro('A Borboleta Amarela'), $assunto);

        try {
            $this->repositorio->excluir($assunto);
        } catch (ExclusaoBloqueada) {
        }

        self::assertNotNull($this->repositorio->buscar($assunto));
    }

    private function livro(string $titulo): int
    {
        return ConexaoDeTeste::inserir(
            'INSERT INTO Livro (Titulo, Editora, Edicao, AnoPublicacao, Valor)
             VALUES (:titulo, :editora, 1, :ano, :valor)',
            ['titulo' => $titulo, 'editora' => 'Objetiva', 'ano' => '1994', 'valor' => '32.00'],
        );
    }

    private function vincular(int $livro, int $assunto): void
    {
        ConexaoDeTeste::inserir(
            'INSERT INTO Livro_Assunto (Livro_Codl, Assunto_codAs) VALUES (:livro, :assunto)',
            ['livro' => $livro, 'assunto' => $assunto],
        );
    }
}
