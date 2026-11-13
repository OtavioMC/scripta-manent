<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Infrastructure\Cache;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use ScriptaManent\Application\Busca;
use ScriptaManent\Application\Cache\Medicoes;
use ScriptaManent\Application\DadosDeExemplo\RestauradorDeDados;
use ScriptaManent\Application\Pagina;
use ScriptaManent\Application\Relatorio\FonteDoRelatorio;
use ScriptaManent\Domain\Assunto\Assunto;
use ScriptaManent\Domain\Autor\Autor;
use ScriptaManent\Domain\ExclusaoBloqueada;
use ScriptaManent\Domain\Livro\Dinheiro;
use ScriptaManent\Domain\Livro\Livro;
use ScriptaManent\Domain\Relatorio\LinhaDoRelatorio;
use ScriptaManent\Infrastructure\Cache\FonteDoRelatorioEmCache;
use ScriptaManent\Infrastructure\Cache\LeituraEmCache;
use ScriptaManent\Infrastructure\Cache\RepositorioDeAssuntosEmCache;
use ScriptaManent\Infrastructure\Cache\RepositorioDeAutoresEmCache;
use ScriptaManent\Infrastructure\Cache\RepositorioDeLivrosEmCache;
use ScriptaManent\Infrastructure\Cache\RestauradorDeDadosEmCache;
use ScriptaManent\Tests\Duble\ArmazemEmMemoria;
use ScriptaManent\Tests\Duble\LogEmMemoria;
use ScriptaManent\Tests\Duble\RepositorioDeAssuntosEmMemoria;
use ScriptaManent\Tests\Duble\RepositorioDeAutoresEmMemoria;
use ScriptaManent\Tests\Duble\RepositorioDeLivrosEmMemoria;

final class RepositoriosEmCacheTest extends TestCase
{
    private ArmazemEmMemoria $armazem;
    private LogEmMemoria $log;
    private LeituraEmCache $cache;
    private RepositorioDeAutoresEmMemoria $autoresNoBanco;
    private RepositorioDeAssuntosEmMemoria $assuntosNoBanco;
    private RepositorioDeLivrosEmMemoria $livrosNoBanco;
    private RepositorioDeAutoresEmCache $autores;
    private RepositorioDeAssuntosEmCache $assuntos;
    private RepositorioDeLivrosEmCache $livros;

    protected function setUp(): void
    {
        $this->armazem = new ArmazemEmMemoria();
        $this->log = new LogEmMemoria();
        $this->cache = new LeituraEmCache($this->armazem, new Medicoes(), $this->log, 300);
        $this->autoresNoBanco = new RepositorioDeAutoresEmMemoria();
        $this->assuntosNoBanco = new RepositorioDeAssuntosEmMemoria();
        $this->livrosNoBanco = new RepositorioDeLivrosEmMemoria($this->autoresNoBanco, $this->assuntosNoBanco);
        $this->autores = new RepositorioDeAutoresEmCache($this->autoresNoBanco, $this->cache);
        $this->assuntos = new RepositorioDeAssuntosEmCache($this->assuntosNoBanco, $this->cache);
        $this->livros = new RepositorioDeLivrosEmCache($this->livrosNoBanco, $this->cache);
    }

    public function testListaDeAutoresVoltaDoCacheIgualADoBanco(): void
    {
        $inserido = $this->autoresNoBanco->inserir(Autor::novo('Clarice Lispector'));

        $this->autores->listar();

        self::assertEquals([$inserido], $this->autores->listar());
        self::assertSame(1, $this->autoresNoBanco->listagens);
    }

    public function testBuscasDiferentesFicamEmEntradasDiferentes(): void
    {
        $this->autoresNoBanco->inserir(Autor::novo('Clarice Lispector'));
        $this->autoresNoBanco->inserir(Autor::novo('Machado de Assis'));

        $this->autores->listar(Busca::deTexto('clarice'));

        self::assertCount(1, $this->autores->listar(Busca::deTexto('machado')));
        self::assertSame(2, $this->autoresNoBanco->listagens);
    }

    public function testCadastrarAutorApareceNaListaEmSeguida(): void
    {
        $this->autores->listar();

        $this->autores->inserir(Autor::novo('Rubem Fonseca'));

        self::assertCount(1, $this->autores->listar());
    }

    public function testRenomearAutorInvalidaAutoresLivrosERelatorio(): void
    {
        $autor = $this->autores->inserir(Autor::novo('Rubem Fonsec'));
        $antes = $this->cache->atuais();

        $this->autores->alterar(Autor::existente((int) $autor->codigo, 'Rubem Fonseca'));

        $depois = $this->cache->atuais();
        self::assertSame(($antes['autores'] ?? 0) + 1, $depois['autores'] ?? null);
        self::assertSame(($antes['livros'] ?? 0) + 1, $depois['livros'] ?? null);
        self::assertSame(($antes['relatorio'] ?? 0) + 1, $depois['relatorio'] ?? null);
    }

    public function testExclusaoBloqueadaNaoInvalidaOCache(): void
    {
        $autor = $this->autores->inserir(Autor::novo('Rubem Fonseca'));
        $this->autoresNoBanco->vincularLivros((int) $autor->codigo, ['Agosto']);
        $antes = $this->cache->atuais();

        try {
            $this->autores->excluir((int) $autor->codigo);
            self::fail('A exclusão deveria ter sido bloqueada.');
        } catch (ExclusaoBloqueada) {
            self::assertSame($antes, $this->cache->atuais());
        }
    }

    public function testRenomearAssuntoInvalidaAssuntosLivrosERelatorio(): void
    {
        $assunto = $this->assuntos->inserir(Assunto::novo('Cronica'));
        $antes = $this->cache->atuais();

        $this->assuntos->alterar(Assunto::existente((int) $assunto->codigo, 'Crônica'));

        $depois = $this->cache->atuais();
        self::assertSame(($antes['assuntos'] ?? 0) + 1, $depois['assuntos'] ?? null);
        self::assertSame(($antes['livros'] ?? 0) + 1, $depois['livros'] ?? null);
        self::assertSame(($antes['relatorio'] ?? 0) + 1, $depois['relatorio'] ?? null);
    }

    public function testListaDeLivrosVoltaDoCacheComTodosOsCampos(): void
    {
        $autor = (int) $this->autoresNoBanco->inserir(Autor::novo('Neil Gaiman'))->codigo;
        $assunto = (int) $this->assuntosNoBanco->inserir(Assunto::novo('Fantasia'))->codigo;
        $this->livrosNoBanco->inserir(
            Livro::criar(null, 'Coraline', 'Intrínseca', '1', '2002', '1.239,90', [$autor], [$assunto]),
        );

        $doBanco = $this->livros->listar();

        self::assertEquals($doBanco, $this->livros->listar());
        self::assertSame(1, $this->livrosNoBanco->listagens);
    }

    public function testPaginaDeLivrosVoltaDoCacheIgualADoBanco(): void
    {
        $this->inserirLivrosNumerados(Pagina::ITENS + 1);

        $doBanco = $this->livros->pagina(Busca::deTexto(''), Pagina::pedidaNaUrl('2'));

        self::assertEquals($doBanco, $this->livros->pagina(Busca::deTexto(''), Pagina::pedidaNaUrl('2')));
        self::assertSame(1, $this->livrosNoBanco->listagens);
    }

    public function testPaginasDiferentesFicamEmEntradasDiferentes(): void
    {
        $this->inserirLivrosNumerados(Pagina::ITENS + 1);
        $this->livros->pagina(Busca::deTexto(''), Pagina::pedidaNaUrl('1'));
        $this->livros->pagina(Busca::deTexto(''), Pagina::pedidaNaUrl('2'));

        $primeira = $this->livros->pagina(Busca::deTexto(''), Pagina::pedidaNaUrl('1'));
        $segunda = $this->livros->pagina(Busca::deTexto(''), Pagina::pedidaNaUrl('2'));

        self::assertCount(Pagina::ITENS, $primeira->itens);
        self::assertSame(
            [sprintf('Livro %02d', Pagina::ITENS + 1)],
            array_map(static fn ($livro): string => $livro->titulo, $segunda->itens),
        );
        self::assertSame(2, $this->livrosNoBanco->listagens);
    }

    public function testBuscasDiferentesNaPaginaFicamEmEntradasDiferentes(): void
    {
        $this->inserirLivrosNumerados(2);
        $this->livros->pagina(Busca::deTexto('Livro 01'), Pagina::pedidaNaUrl('1'));
        $this->livros->pagina(Busca::deTexto('Livro 02'), Pagina::pedidaNaUrl('1'));

        $pagina = $this->livros->pagina(Busca::deTexto('Livro 02'), Pagina::pedidaNaUrl('1'));

        self::assertSame(['Livro 02'], array_map(static fn ($livro): string => $livro->titulo, $pagina->itens));
        self::assertSame(2, $this->livrosNoBanco->listagens);
    }

    public function testExcluirLivroFazAPaginaSerCalculadaDeNovo(): void
    {
        $codigos = $this->inserirLivrosNumerados(Pagina::ITENS + 1);
        $this->livros->pagina(Busca::deTexto(''), Pagina::pedidaNaUrl('2'));
        $this->livros->pagina(Busca::deTexto(''), Pagina::pedidaNaUrl('2'));

        $this->livros->excluir($codigos[Pagina::ITENS]);
        $pagina = $this->livros->pagina(Busca::deTexto(''), Pagina::pedidaNaUrl('2'));

        self::assertSame(1, $pagina->pagina->numero);
        self::assertSame(Pagina::ITENS, $pagina->totalDeItens);
        self::assertSame(2, $this->livrosNoBanco->listagens);
    }

    public function testGravarLivroInvalidaLivrosERelatorioMasNaoAutores(): void
    {
        $antes = $this->cache->atuais();

        $this->livros->inserir(Livro::criar(null, 'Coraline', 'Intrínseca', '1', '2002', '39,90', [], []));

        $depois = $this->cache->atuais();
        self::assertSame(($antes['livros'] ?? 0) + 1, $depois['livros'] ?? null);
        self::assertSame(($antes['relatorio'] ?? 0) + 1, $depois['relatorio'] ?? null);
        self::assertSame($antes['autores'] ?? null, $depois['autores'] ?? null);
    }

    public function testRelatorioVoltaDoCacheComLivroSemAutor(): void
    {
        $linhas = [
            new LinhaDoRelatorio(5, 'Neil Gaiman', 20, 'Coraline', 'Rocco', 1, '2002', Dinheiro::zero(), 'Fantasia'),
            new LinhaDoRelatorio(null, null, 30, 'Mil Noites', 'Globo', 1, '2017', Dinheiro::deTexto('79,90'), ''),
        ];
        $fonte = new class ($linhas) implements FonteDoRelatorio {
            public int $leituras = 0;

            /**
             * @param list<LinhaDoRelatorio> $linhas
             */
            public function __construct(private readonly array $linhas)
            {
            }

            public function linhas(?Busca $busca = null): array
            {
                $this->leituras++;

                return $this->linhas;
            }
        };
        $comCache = new FonteDoRelatorioEmCache($fonte, $this->cache);

        $comCache->linhas();

        self::assertEquals($linhas, $comCache->linhas());
        self::assertSame(1, $fonte->leituras);
    }

    public function testRestaurarOsDadosFazTodasAsTelasVoltaremAoBanco(): void
    {
        $this->autoresNoBanco->inserir(Autor::novo('Cadastrado Depois'));
        $this->autores->listar();
        $this->assuntos->listar();
        $this->livros->listar();
        $fonte = $this->fonteQueConta();
        $relatorio = new FonteDoRelatorioEmCache($fonte, $this->cache);
        $relatorio->linhas();

        $this->restaurador($this->restauracaoQueApagaOsAutores())->restaurar();
        $listagensDeAutores = $this->autoresNoBanco->listagens;

        self::assertSame([], $this->autores->listar());
        $this->assuntos->listar();
        $this->livros->listar();
        $relatorio->linhas();
        self::assertSame($listagensDeAutores + 1, $this->autoresNoBanco->listagens);
        self::assertSame(2, $this->assuntosNoBanco->listagens);
        self::assertSame(2, $this->livrosNoBanco->listagens);
        self::assertSame(2, $fonte->leituras);
    }

    public function testRestaurarInvalidaDepoisDeTerminarNoBanco(): void
    {
        $restauracao = new class ($this->cache) implements RestauradorDeDados {
            public ?int $versaoDurante = null;

            public function __construct(private readonly LeituraEmCache $cache)
            {
            }

            public function restaurar(): void
            {
                $this->versaoDurante = $this->cache->atuais()['livros'] ?? null;
            }
        };

        $this->restaurador($restauracao)->restaurar();

        self::assertSame(0, $restauracao->versaoDurante);
        self::assertSame(1, $this->cache->atuais()['livros'] ?? null);
    }

    public function testRestauracaoQueFalhaNaoInvalidaOCache(): void
    {
        $falha = new class implements RestauradorDeDados {
            public function restaurar(): void
            {
                throw new RuntimeException('falha no banco');
            }
        };

        try {
            $this->restaurador($falha)->restaurar();
            self::fail('A falha deveria ter subido.');
        } catch (RuntimeException) {
            self::assertSame(0, $this->cache->atuais()['livros'] ?? null);
        }
    }

    public function testRestaurarComORedisForaDoArFuncionaERegistraErroEmCadaColecao(): void
    {
        $this->armazem->foraDoAr = true;

        $this->restaurador($this->restauracaoQueApagaOsAutores())->restaurar();

        self::assertCount(4, $this->log->registros);
        self::assertSame('error', $this->log->registros[0]['nivel']);
    }

    /**
     * @return list<int>
     */
    private function inserirLivrosNumerados(int $quantidade): array
    {
        $codigos = [];
        for ($numero = 1; $numero <= $quantidade; $numero++) {
            $codigos[] = (int) $this->livrosNoBanco->inserir(
                Livro::criar(null, sprintf('Livro %02d', $numero), 'Rocco', '1', '2020', '10,00', [], []),
            )->codigo;
        }

        return $codigos;
    }

    private function restaurador(RestauradorDeDados $restauracao): RestauradorDeDadosEmCache
    {
        return new RestauradorDeDadosEmCache($restauracao, $this->cache);
    }

    private function restauracaoQueApagaOsAutores(): RestauradorDeDados
    {
        return new class ($this->autoresNoBanco) implements RestauradorDeDados {
            public function __construct(private readonly RepositorioDeAutoresEmMemoria $autores)
            {
            }

            public function restaurar(): void
            {
                foreach ($this->autores->listar() as $autor) {
                    $this->autores->excluir((int) $autor->codigo);
                }
            }
        };
    }

    /**
     * @return FonteDoRelatorio&object{leituras: int}
     */
    private function fonteQueConta(): FonteDoRelatorio
    {
        return new class implements FonteDoRelatorio {
            public int $leituras = 0;

            public function linhas(?Busca $busca = null): array
            {
                $this->leituras++;

                return [];
            }
        };
    }
}
