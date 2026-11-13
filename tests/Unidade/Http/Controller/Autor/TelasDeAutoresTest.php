<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http\Controller\Autor;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ScriptaManent\Application\Autor\CadastroDeAutores;
use ScriptaManent\Application\Pagina;
use ScriptaManent\Domain\Autor\Autor;
use ScriptaManent\Http\Apresentador\FormularioDeAutorApresentador;
use ScriptaManent\Http\Controller\Autor\ExcluirAutorController;
use ScriptaManent\Http\Controller\Autor\FormularioDeAutorController;
use ScriptaManent\Http\Controller\Autor\ListaDeAutoresController;
use ScriptaManent\Http\Controller\Autor\SalvarAutorController;
use ScriptaManent\Http\PaginaDeErro;
use ScriptaManent\Http\Visao;
use ScriptaManent\Tests\Duble\RepositorioDeAutoresEmMemoria;
use ScriptaManent\Tests\Duble\SessaoEmMemoria;
use ScriptaManent\Tests\Duble\TwigDeTeste;

final class TelasDeAutoresTest extends TestCase
{
    private RepositorioDeAutoresEmMemoria $repositorio;
    private SessaoEmMemoria $sessao;
    private Visao $visao;
    private CadastroDeAutores $cadastro;
    private FormularioDeAutorApresentador $formulario;

    protected function setUp(): void
    {
        $twig = TwigDeTeste::criar();

        $this->repositorio = new RepositorioDeAutoresEmMemoria();
        $this->sessao = new SessaoEmMemoria();
        $this->visao = new Visao($twig, new Psr17Factory());
        $this->cadastro = new CadastroDeAutores($this->repositorio);
        $this->formulario = new FormularioDeAutorApresentador($this->visao, new PaginaDeErro($this->visao));
    }

    public function testListaMostraOsAutoresComLinksDeEdicao(): void
    {
        $autor = $this->repositorio->inserir(Autor::novo('Clarice Lispector'));

        $pagina = (string) $this->lista()->getBody();

        self::assertStringContainsString('Clarice Lispector', $pagina);
        self::assertStringContainsString(sprintf('href="/autores/%d/editar"', (int) $autor->codigo), $pagina);
    }

    public function testListaTrazOFormularioEmModalEOsDadosParaEditar(): void
    {
        $autor = $this->repositorio->inserir(Autor::novo('Clarice Lispector'));

        $pagina = (string) $this->lista()->getBody();

        self::assertStringContainsString('id="modal-formulario"', $pagina);
        self::assertMatchesRegularExpression(
            sprintf('#<input [^>]*name="nome"[^>]*maxlength="%d"#', Autor::NOME_MAXIMO),
            $pagina,
        );
        self::assertStringContainsString(sprintf('data-acao="/autores/%d"', (int) $autor->codigo), $pagina);
        self::assertStringContainsString('data-campos="{&quot;nome&quot;:&quot;Clarice Lispector&quot;}"', $pagina);
    }

    public function testMenuDestacaATelaDeAutores(): void
    {
        $pagina = (string) $this->lista()->getBody();

        self::assertStringContainsString('<a class="nav-link active" aria-current="page" href="/autores">', $pagina);
        self::assertSame(1, substr_count($pagina, 'aria-current="page" href='));
    }

    public function testListaVaziaDizComoComecar(): void
    {
        $pagina = (string) $this->lista()->getBody();

        self::assertStringContainsString('Nenhum autor cadastrado.', $pagina);
        self::assertStringContainsString('href="/autores/novo"', $pagina);
    }

    public function testAvisoDaSessaoApareceUmaVezSo(): void
    {
        $this->sessao->guardar('aviso', 'Autor Clarice Lispector cadastrado.');

        self::assertStringContainsString('Autor Clarice Lispector cadastrado.', (string) $this->lista()->getBody());
        self::assertStringNotContainsString('Autor Clarice Lispector cadastrado.', (string) $this->lista()->getBody());
    }

    public function testBuscaFiltraAListaEMostraQuantosForamEncontrados(): void
    {
        $this->repositorio->inserir(Autor::novo('Clarice Lispector'));
        $this->repositorio->inserir(Autor::novo('Machado de Assis'));

        $lista = new ListaDeAutoresController($this->visao, $this->cadastro, $this->sessao, $this->formulario);
        $requisicao = (new ServerRequest('GET', '/autores'))->withQueryParams(['busca' => 'machado']);
        $pagina = (string) $lista->handle($requisicao)->getBody();

        self::assertStringContainsString('Machado de Assis', $pagina);
        self::assertStringNotContainsString('Clarice Lispector', $pagina);
        self::assertStringContainsString('1 autor encontrado para “machado”.', $pagina);
    }

    public function testSegundaPaginaMostraOsAutoresDepoisDaPrimeiraPaginaCheia(): void
    {
        $this->inserirAutoresNumerados(Pagina::ITENS + 1);

        $pagina = (string) $this->lista(['pagina' => '2'])->getBody();

        self::assertStringContainsString(sprintf('Autor %02d', Pagina::ITENS + 1), $pagina);
        self::assertStringNotContainsString('Autor 01', $pagina);
        self::assertStringContainsString(
            sprintf('Mostrando %1$d a %1$d de %1$d autores.', Pagina::ITENS + 1),
            $pagina,
        );
    }

    public function testNavegacaoDeAutoresTemOsNumerosEMantemABusca(): void
    {
        $this->inserirAutoresNumerados(2 * Pagina::ITENS + 1);

        $pagina = (string) $this->lista(['busca' => 'Autor', 'pagina' => '2'])->getBody();

        self::assertMatchesRegularExpression('#href="/autores\?busca=Autor&amp;pagina=1"[^>]*>1</a>#', $pagina);
        self::assertMatchesRegularExpression(
            '#href="/autores\?busca=Autor&amp;pagina=3"[^>]*aria-label="Próxima página"#',
            $pagina,
        );
        self::assertStringContainsString('Páginas da lista de autores', $pagina);
    }

    public function testBuscaContaTodosOsAutoresEncontradosENaoSoOsDaPagina(): void
    {
        $this->inserirAutoresNumerados(Pagina::ITENS + 1);

        $pagina = (string) $this->lista(['busca' => 'Autor'])->getBody();

        self::assertStringContainsString(sprintf('%d autores encontrados para', Pagina::ITENS + 1), $pagina);
    }

    public function testPaginaDeAutoresAlemDaUltimaRedirecionaParaAUltimaMantendoABusca(): void
    {
        $this->inserirAutoresNumerados(Pagina::ITENS + 1);

        $resposta = $this->lista(['busca' => 'Autor', 'pagina' => '99']);

        self::assertSame(303, $resposta->getStatusCode());
        self::assertSame('/autores?busca=Autor&pagina=2', $resposta->getHeaderLine('Location'));
    }

    public function testAvisoSobreviveAoRedirecionamentoDaPaginaDeAutores(): void
    {
        $this->inserirAutoresNumerados(Pagina::ITENS + 1);
        $this->sessao->guardar('aviso', 'Autor Clarice Lispector excluído.');

        $this->lista(['pagina' => '99']);

        self::assertStringContainsString(
            'Autor Clarice Lispector excluído.',
            (string) $this->lista(['pagina' => '2'])->getBody(),
        );
    }

    public function testModalDeBloqueioLevaParaOsLivrosDoAutor(): void
    {
        $autor = $this->repositorio->inserir(Autor::novo('Machado de Assis'));
        $this->repositorio->vincularLivros((int) $autor->codigo, ['Dom Casmurro']);

        $this->excluir((int) $autor->codigo);

        self::assertStringContainsString(
            'href="/livros?busca=Machado%20de%20Assis"',
            (string) $this->lista()->getBody(),
        );
    }

    public function testFormularioUsaOLimiteDoDominioNoCampoNome(): void
    {
        $pagina = (string) $this->abrirFormulario()->getBody();

        self::assertMatchesRegularExpression(
            sprintf('#<input [^>]*name="nome"[^>]*maxlength="%d" required#', Autor::NOME_MAXIMO),
            $pagina,
        );
        self::assertStringContainsString(Autor::NOME_OBRIGATORIO, $pagina);
    }

    public function testFormularioDeEdicaoTrazONomeAtual(): void
    {
        $autor = $this->repositorio->inserir(Autor::novo('Clarice Lispector'));

        $pagina = (string) $this->abrirFormulario((int) $autor->codigo)->getBody();

        self::assertStringContainsString('value="Clarice Lispector"', $pagina);
        self::assertStringContainsString(sprintf('action="/autores/%d"', (int) $autor->codigo), $pagina);
    }

    public function testEdicaoDeAutorInexistenteRespondeNaoEncontrado(): void
    {
        $resposta = $this->abrirFormulario(999);

        self::assertSame(404, $resposta->getStatusCode());
        self::assertStringContainsString('Nenhum autor cadastrado tem o código 999.', (string) $resposta->getBody());
    }

    public function testCadastraERedirecionaParaAListaComAviso(): void
    {
        $resposta = $this->salvar(null, 'Rubem Fonseca');

        self::assertSame(303, $resposta->getStatusCode());
        self::assertSame('/autores', $resposta->getHeaderLine('Location'));
        self::assertSame('Rubem Fonseca', $this->repositorio->listar()[0]->nome ?? null);
        self::assertSame('Autor Rubem Fonseca cadastrado.', $this->sessao->obter('aviso'));
    }

    public function testNomeInvalidoReabreOFormularioComAMensagemEOValorDigitado(): void
    {
        $nome = str_repeat('a', Autor::NOME_MAXIMO + 1);

        $resposta = $this->salvar(null, $nome);

        self::assertSame(422, $resposta->getStatusCode());
        self::assertStringContainsString('Nome aceita até 40 caracteres.', (string) $resposta->getBody());
        self::assertStringContainsString(sprintf('value="%s"', $nome), (string) $resposta->getBody());
        self::assertSame([], $this->repositorio->listar());
    }

    public function testAlteraOAutorExistente(): void
    {
        $autor = $this->repositorio->inserir(Autor::novo('Rubem Fonsec'));

        $resposta = $this->salvar((int) $autor->codigo, 'Rubem Fonseca');

        self::assertSame(303, $resposta->getStatusCode());
        self::assertSame('Rubem Fonseca', $this->repositorio->buscar((int) $autor->codigo)?->nome);
        self::assertSame('Autor Rubem Fonseca alterado.', $this->sessao->obter('aviso'));
    }

    public function testAlterarAutorInexistenteRespondeNaoEncontrado(): void
    {
        self::assertSame(404, $this->salvar(999, 'Rubem Fonseca')->getStatusCode());
    }

    public function testExcluiERedirecionaComAviso(): void
    {
        $autor = $this->repositorio->inserir(Autor::novo('Rubem Fonseca'));

        $resposta = $this->excluir((int) $autor->codigo);

        self::assertSame(303, $resposta->getStatusCode());
        self::assertNull($this->repositorio->buscar((int) $autor->codigo));
        self::assertSame('Autor Rubem Fonseca excluído.', $this->sessao->obter('aviso'));
    }

    public function testExclusaoBloqueadaAbreOModalComOsLivrosNaLista(): void
    {
        $autor = $this->repositorio->inserir(Autor::novo('Rubem Fonseca'));
        $this->repositorio->vincularLivros((int) $autor->codigo, ['Agosto', 'Feliz Ano Novo']);

        $this->excluir((int) $autor->codigo);
        $pagina = (string) $this->lista()->getBody();

        self::assertNotNull($this->repositorio->buscar((int) $autor->codigo));
        self::assertStringContainsString('data-abrir-ao-carregar', $pagina);
        self::assertStringContainsString('O autor Rubem Fonseca está vinculado a 2 livros.', $pagina);
        self::assertStringContainsString('<li>Agosto</li>', $pagina);
        self::assertStringContainsString('<li>Feliz Ano Novo</li>', $pagina);
    }

    public function testExcluirAutorInexistenteRespondeNaoEncontrado(): void
    {
        self::assertSame(404, $this->excluir(999)->getStatusCode());
    }

    private function inserirAutoresNumerados(int $quantidade): void
    {
        for ($numero = 1; $numero <= $quantidade; $numero++) {
            $this->repositorio->inserir(Autor::novo(sprintf('Autor %02d', $numero)));
        }
    }

    /**
     * @param array<string, string> $consulta
     */
    private function lista(array $consulta = []): ResponseInterface
    {
        return (new ListaDeAutoresController($this->visao, $this->cadastro, $this->sessao, $this->formulario))
            ->handle((new ServerRequest('GET', '/autores'))->withQueryParams($consulta));
    }

    private function abrirFormulario(?int $codigo = null): ResponseInterface
    {
        $requisicao = new ServerRequest('GET', '/autores/novo');
        if ($codigo !== null) {
            $requisicao = $requisicao->withAttribute('codigo', (string) $codigo);
        }

        return (new FormularioDeAutorController($this->formulario, $this->cadastro))->handle($requisicao);
    }

    private function salvar(?int $codigo, string $nome): ResponseInterface
    {
        $requisicao = (new ServerRequest('POST', '/autores'))->withParsedBody(['nome' => $nome]);
        if ($codigo !== null) {
            $requisicao = $requisicao->withAttribute('codigo', (string) $codigo);
        }

        return (new SalvarAutorController($this->formulario, $this->cadastro, $this->sessao, $this->visao))
            ->handle($requisicao);
    }

    private function excluir(int $codigo): ResponseInterface
    {
        $requisicao = (new ServerRequest('POST', '/autores/excluir'))->withAttribute('codigo', (string) $codigo);

        return (new ExcluirAutorController($this->formulario, $this->cadastro, $this->sessao, $this->visao))
            ->handle($requisicao);
    }
}
