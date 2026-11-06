<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http\Controller\Livro;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ScriptaManent\Application\Assunto\CadastroDeAssuntos;
use ScriptaManent\Application\Autor\CadastroDeAutores;
use ScriptaManent\Application\Livro\CadastroDeLivros;
use ScriptaManent\Domain\Assunto\Assunto;
use ScriptaManent\Domain\Autor\Autor;
use ScriptaManent\Domain\Livro\Livro;
use ScriptaManent\Http\Apresentador\FormularioDeLivroApresentador;
use ScriptaManent\Http\Controller\Livro\ExcluirLivroController;
use ScriptaManent\Http\Controller\Livro\FormularioDeLivroController;
use ScriptaManent\Http\Controller\Livro\ListaDeLivrosController;
use ScriptaManent\Http\Controller\Livro\SalvarLivroController;
use ScriptaManent\Http\PaginaDeErro;
use ScriptaManent\Http\Visao;
use ScriptaManent\Tests\Duble\RepositorioDeAssuntosEmMemoria;
use ScriptaManent\Tests\Duble\RepositorioDeAutoresEmMemoria;
use ScriptaManent\Tests\Duble\RepositorioDeLivrosEmMemoria;
use ScriptaManent\Tests\Duble\SessaoEmMemoria;
use Twig\Environment;

final class TelasDeLivrosTest extends TestCase
{
    private RepositorioDeAutoresEmMemoria $autores;
    private RepositorioDeAssuntosEmMemoria $assuntos;
    private RepositorioDeLivrosEmMemoria $livros;
    private SessaoEmMemoria $sessao;
    private Visao $visao;
    private CadastroDeLivros $cadastro;
    private FormularioDeLivroApresentador $formulario;

    protected function setUp(): void
    {
        $twig = require __DIR__ . '/../../../../../config/twig.php';
        self::assertInstanceOf(Environment::class, $twig);

        $this->autores = new RepositorioDeAutoresEmMemoria();
        $this->assuntos = new RepositorioDeAssuntosEmMemoria();
        $this->livros = new RepositorioDeLivrosEmMemoria($this->autores, $this->assuntos);
        $this->sessao = new SessaoEmMemoria();
        $this->visao = new Visao($twig, new Psr17Factory());
        $this->cadastro = new CadastroDeLivros($this->livros);
        $this->formulario = new FormularioDeLivroApresentador(
            $this->visao,
            new PaginaDeErro($this->visao),
            new CadastroDeAutores($this->autores),
            new CadastroDeAssuntos($this->assuntos),
        );
    }

    public function testListaMostraOLivroComAutoresEValorFormatado(): void
    {
        $autor = (int) $this->autores->inserir(Autor::novo('Machado de Assis'))->codigo;
        $this->livros->inserir(Livro::criar(null, 'Dom Casmurro', 'Ática', '2', '1899', '1.234,56', [$autor], []));

        $pagina = (string) $this->lista()->getBody();

        self::assertStringContainsString('Dom Casmurro', $pagina);
        self::assertStringContainsString('Machado de Assis', $pagina);
        self::assertStringContainsString('R$ 1.234,56', $pagina);
        self::assertStringContainsString('Sem assunto', $pagina);
    }

    public function testListaTrazOFormularioEmModalEOsDadosParaEditar(): void
    {
        $autor = (int) $this->autores->inserir(Autor::novo('Neil Gaiman'))->codigo;
        $livro = $this->livros->inserir(
            Livro::criar(null, 'Coraline', 'Intrínseca', '1', '2002', '1.239,90', [$autor], []),
        );

        $pagina = (string) $this->lista()->getBody();

        self::assertStringContainsString('id="modal-formulario"', $pagina);
        self::assertStringContainsString(sprintf('data-acao="/livros/%d"', (int) $livro->codigo), $pagina);
        self::assertStringContainsString('&quot;valor&quot;:&quot;1.239,90&quot;', $pagina);
        self::assertStringContainsString(sprintf('&quot;autores&quot;:[%d]', $autor), $pagina);
    }

    public function testListaVaziaDizComoComecar(): void
    {
        self::assertStringContainsString('Nenhum livro cadastrado.', (string) $this->lista()->getBody());
    }

    public function testFormularioUsaOsLimitesDoDominioEListaAutoresEAssuntos(): void
    {
        $this->autores->inserir(Autor::novo('Neil Gaiman'));
        $this->assuntos->inserir(Assunto::novo('Fantasia'));

        $pagina = (string) $this->abrirFormulario()->getBody();

        self::assertMatchesRegularExpression('#name="titulo"[^>]*maxlength="40"#', $pagina);
        self::assertMatchesRegularExpression('#name="edicao"[^>]*min="1" max="999"#', $pagina);
        self::assertMatchesRegularExpression('#name="valor"[^>]*data-mascara="moeda"#', $pagina);
        self::assertStringContainsString('name="autores[]"', $pagina);
        self::assertStringContainsString('Neil Gaiman', $pagina);
        self::assertStringContainsString('Fantasia', $pagina);
    }

    public function testFormularioDeEdicaoTrazOsDadosEOsVinculosMarcados(): void
    {
        $autor = (int) $this->autores->inserir(Autor::novo('Neil Gaiman'))->codigo;
        $livro = $this->livros->inserir(
            Livro::criar(null, 'Coraline', 'Intrínseca', '1', '2002', '39,90', [$autor], []),
        );

        $pagina = (string) $this->abrirFormulario((int) $livro->codigo)->getBody();

        self::assertStringContainsString('value="Coraline"', $pagina);
        self::assertStringContainsString('value="39,90"', $pagina);
        self::assertMatchesRegularExpression(sprintf('#value="%d"\s+id="autores-%1$d" checked#', $autor), $pagina);
    }

    public function testEdicaoDeLivroInexistenteRespondeNaoEncontrado(): void
    {
        self::assertSame(404, $this->abrirFormulario(999)->getStatusCode());
    }

    public function testCadastraComVinculosERedirecionaComAviso(): void
    {
        $autor = (int) $this->autores->inserir(Autor::novo('Neil Gaiman'))->codigo;

        $resposta = $this->salvar(null, $this->campos(), ['autores' => [(string) $autor]]);

        self::assertSame(303, $resposta->getStatusCode());
        self::assertSame('/livros', $resposta->getHeaderLine('Location'));
        self::assertSame([$autor], $this->livros->buscar(1)?->autores);
        self::assertSame('Livro Coraline cadastrado.', $this->sessao->obter('aviso'));
    }

    public function testDadosInvalidosReabremOFormularioComAsMensagensEOsValores(): void
    {
        $autor = (int) $this->autores->inserir(Autor::novo('Neil Gaiman'))->codigo;

        $resposta = $this->salvar(
            null,
            [...$this->campos(), 'ano' => '99', 'valor' => '12.5'],
            ['autores' => [(string) $autor]],
        );
        $pagina = (string) $resposta->getBody();

        self::assertSame(422, $resposta->getStatusCode());
        self::assertStringContainsString('Ano de publicação precisa ter 4 dígitos, como 1999.', $pagina);
        self::assertStringContainsString('Valor precisa estar no formato 1.234,56.', $pagina);
        self::assertStringContainsString('value="Coraline"', $pagina);
        self::assertMatchesRegularExpression(sprintf('#id="autores-%d" checked#', $autor), $pagina);
        self::assertSame([], $this->livros->listar());
    }

    public function testAlteraOLivroExistente(): void
    {
        $livro = $this->livros->inserir(Livro::criar(null, 'Corali', 'Intrínseca', '1', '2002', '39,90', [], []));

        $resposta = $this->salvar((int) $livro->codigo, $this->campos(), []);

        self::assertSame(303, $resposta->getStatusCode());
        self::assertSame('Coraline', $this->livros->buscar((int) $livro->codigo)?->titulo);
        self::assertSame('Livro Coraline alterado.', $this->sessao->obter('aviso'));
    }

    public function testExcluiERedirecionaComAviso(): void
    {
        $livro = $this->livros->inserir(Livro::criar(null, 'Coraline', 'Intrínseca', '1', '2002', '39,90', [], []));

        $resposta = $this->excluir((int) $livro->codigo);

        self::assertSame(303, $resposta->getStatusCode());
        self::assertNull($this->livros->buscar((int) $livro->codigo));
        self::assertSame('Livro Coraline excluído.', $this->sessao->obter('aviso'));
    }

    public function testExcluirLivroInexistenteRespondeNaoEncontrado(): void
    {
        self::assertSame(404, $this->excluir(999)->getStatusCode());
    }

    /**
     * @return array<string, string>
     */
    private function campos(): array
    {
        return [
            'titulo' => 'Coraline',
            'editora' => 'Intrínseca',
            'edicao' => '1',
            'ano' => '2002',
            'valor' => '39,90',
        ];
    }

    private function lista(): ResponseInterface
    {
        return (new ListaDeLivrosController($this->visao, $this->cadastro, $this->sessao, $this->formulario))
            ->handle(new ServerRequest('GET', '/livros'));
    }

    private function abrirFormulario(?int $codigo = null): ResponseInterface
    {
        $requisicao = new ServerRequest('GET', '/livros/novo');
        if ($codigo !== null) {
            $requisicao = $requisicao->withAttribute('codigo', (string) $codigo);
        }

        return (new FormularioDeLivroController($this->formulario, $this->cadastro))->handle($requisicao);
    }

    /**
     * @param array<string, string> $campos
     * @param array<string, list<string>> $vinculos
     */
    private function salvar(?int $codigo, array $campos, array $vinculos): ResponseInterface
    {
        $requisicao = (new ServerRequest('POST', '/livros'))->withParsedBody([...$campos, ...$vinculos]);
        if ($codigo !== null) {
            $requisicao = $requisicao->withAttribute('codigo', (string) $codigo);
        }

        return (new SalvarLivroController($this->formulario, $this->cadastro, $this->sessao, $this->visao))
            ->handle($requisicao);
    }

    private function excluir(int $codigo): ResponseInterface
    {
        $requisicao = (new ServerRequest('POST', '/livros/excluir'))->withAttribute('codigo', (string) $codigo);

        return (new ExcluirLivroController($this->formulario, $this->cadastro, $this->sessao, $this->visao))
            ->handle($requisicao);
    }
}
