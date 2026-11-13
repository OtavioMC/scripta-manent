<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http\Controller\Assunto;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ScriptaManent\Application\Assunto\CadastroDeAssuntos;
use ScriptaManent\Application\Pagina;
use ScriptaManent\Domain\Assunto\Assunto;
use ScriptaManent\Http\Apresentador\FormularioDeAssuntoApresentador;
use ScriptaManent\Http\Controller\Assunto\ExcluirAssuntoController;
use ScriptaManent\Http\Controller\Assunto\FormularioDeAssuntoController;
use ScriptaManent\Http\Controller\Assunto\ListaDeAssuntosController;
use ScriptaManent\Http\Controller\Assunto\SalvarAssuntoController;
use ScriptaManent\Http\PaginaDeErro;
use ScriptaManent\Http\Visao;
use ScriptaManent\Tests\Duble\RepositorioDeAssuntosEmMemoria;
use ScriptaManent\Tests\Duble\SessaoEmMemoria;
use ScriptaManent\Tests\Duble\TwigDeTeste;

final class TelasDeAssuntosTest extends TestCase
{
    private RepositorioDeAssuntosEmMemoria $repositorio;
    private SessaoEmMemoria $sessao;
    private Visao $visao;
    private CadastroDeAssuntos $cadastro;
    private FormularioDeAssuntoApresentador $formulario;

    protected function setUp(): void
    {
        $twig = TwigDeTeste::criar();

        $this->repositorio = new RepositorioDeAssuntosEmMemoria();
        $this->sessao = new SessaoEmMemoria();
        $this->visao = new Visao($twig, new Psr17Factory());
        $this->cadastro = new CadastroDeAssuntos($this->repositorio);
        $this->formulario = new FormularioDeAssuntoApresentador($this->visao, new PaginaDeErro($this->visao));
    }

    public function testListaMostraOsAssuntosComLinksDeEdicao(): void
    {
        $assunto = $this->repositorio->inserir(Assunto::novo('Poesia'));

        $pagina = (string) $this->lista()->getBody();

        self::assertStringContainsString('Poesia', $pagina);
        self::assertStringContainsString(sprintf('href="/assuntos/%d/editar"', (int) $assunto->codigo), $pagina);
    }

    public function testListaTrazOFormularioEmModalEOsDadosParaEditar(): void
    {
        $assunto = $this->repositorio->inserir(Assunto::novo('Poesia'));

        $pagina = (string) $this->lista()->getBody();

        self::assertStringContainsString('id="modal-formulario"', $pagina);
        self::assertMatchesRegularExpression(
            sprintf('#<input [^>]*name="descricao"[^>]*maxlength="%d"#', Assunto::DESCRICAO_MAXIMA),
            $pagina,
        );
        self::assertStringContainsString(sprintf('data-acao="/assuntos/%d"', (int) $assunto->codigo), $pagina);
        self::assertStringContainsString('data-campos="{&quot;descricao&quot;:&quot;Poesia&quot;}"', $pagina);
    }

    public function testListaVaziaDizComoComecar(): void
    {
        $pagina = (string) $this->lista()->getBody();

        self::assertStringContainsString('Nenhum assunto cadastrado.', $pagina);
        self::assertStringContainsString('href="/assuntos/novo"', $pagina);
    }

    public function testAvisoDaSessaoApareceUmaVezSo(): void
    {
        $this->sessao->guardar('aviso', 'Assunto Poesia cadastrado.');

        self::assertStringContainsString('Assunto Poesia cadastrado.', (string) $this->lista()->getBody());
        self::assertStringNotContainsString('Assunto Poesia cadastrado.', (string) $this->lista()->getBody());
    }

    public function testSegundaPaginaMostraOsAssuntosDepoisDaPrimeiraPaginaCheia(): void
    {
        $this->inserirAssuntosNumerados(Pagina::ITENS + 1);

        $pagina = (string) $this->lista(['pagina' => '2'])->getBody();

        self::assertStringContainsString(sprintf('Assunto %02d', Pagina::ITENS + 1), $pagina);
        self::assertStringNotContainsString('Assunto 01', $pagina);
        self::assertStringContainsString(
            sprintf('Mostrando %1$d a %1$d de %1$d assuntos.', Pagina::ITENS + 1),
            $pagina,
        );
    }

    public function testNavegacaoDeAssuntosTemOsNumerosEMantemABusca(): void
    {
        $this->inserirAssuntosNumerados(2 * Pagina::ITENS + 1);

        $pagina = (string) $this->lista(['busca' => 'Assunto', 'pagina' => '2'])->getBody();

        self::assertMatchesRegularExpression('#href="/assuntos\?busca=Assunto&amp;pagina=1"[^>]*>1</a>#', $pagina);
        self::assertMatchesRegularExpression(
            '#href="/assuntos\?busca=Assunto&amp;pagina=3"[^>]*aria-label="Próxima página"#',
            $pagina,
        );
        self::assertStringContainsString('Páginas da lista de assuntos', $pagina);
    }

    public function testBuscaContaTodosOsAssuntosEncontradosENaoSoOsDaPagina(): void
    {
        $this->inserirAssuntosNumerados(Pagina::ITENS + 1);

        $pagina = (string) $this->lista(['busca' => 'Assunto'])->getBody();

        self::assertStringContainsString(sprintf('%d assuntos encontrados para', Pagina::ITENS + 1), $pagina);
    }

    public function testPaginaDeAssuntosAlemDaUltimaRedirecionaParaAUltimaMantendoABusca(): void
    {
        $this->inserirAssuntosNumerados(Pagina::ITENS + 1);

        $resposta = $this->lista(['busca' => 'Assunto', 'pagina' => '99']);

        self::assertSame(303, $resposta->getStatusCode());
        self::assertSame('/assuntos?busca=Assunto&pagina=2', $resposta->getHeaderLine('Location'));
    }

    public function testAvisoSobreviveAoRedirecionamentoDaPaginaDeAssuntos(): void
    {
        $this->inserirAssuntosNumerados(Pagina::ITENS + 1);
        $this->sessao->guardar('aviso', 'Assunto Crônica excluído.');

        $this->lista(['pagina' => '99']);

        self::assertStringContainsString(
            'Assunto Crônica excluído.',
            (string) $this->lista(['pagina' => '2'])->getBody(),
        );
    }

    public function testFormularioUsaOLimiteDoDominioNoCampoDescricao(): void
    {
        $pagina = (string) $this->abrirFormulario()->getBody();

        self::assertMatchesRegularExpression(
            sprintf('#<input [^>]*name="descricao"[^>]*maxlength="%d" required#', Assunto::DESCRICAO_MAXIMA),
            $pagina,
        );
        self::assertStringContainsString(Assunto::DESCRICAO_OBRIGATORIA, $pagina);
    }

    public function testFormularioDeEdicaoTrazADescricaoAtual(): void
    {
        $assunto = $this->repositorio->inserir(Assunto::novo('Poesia'));

        $pagina = (string) $this->abrirFormulario((int) $assunto->codigo)->getBody();

        self::assertStringContainsString('value="Poesia"', $pagina);
        self::assertStringContainsString(sprintf('action="/assuntos/%d"', (int) $assunto->codigo), $pagina);
    }

    public function testEdicaoDeAssuntoInexistenteRespondeNaoEncontrado(): void
    {
        $resposta = $this->abrirFormulario(999);

        self::assertSame(404, $resposta->getStatusCode());
        self::assertStringContainsString('Nenhum assunto cadastrado tem o código 999.', (string) $resposta->getBody());
    }

    public function testCadastraERedirecionaParaAListaComAviso(): void
    {
        $resposta = $this->salvar(null, 'Crônica');

        self::assertSame(303, $resposta->getStatusCode());
        self::assertSame('/assuntos', $resposta->getHeaderLine('Location'));
        self::assertSame('Crônica', $this->repositorio->listar()[0]->descricao ?? null);
        self::assertSame('Assunto Crônica cadastrado.', $this->sessao->obter('aviso'));
    }

    public function testDescricaoInvalidaReabreOFormularioComAMensagemEOValorDigitado(): void
    {
        $descricao = str_repeat('a', Assunto::DESCRICAO_MAXIMA + 1);

        $resposta = $this->salvar(null, $descricao);

        self::assertSame(422, $resposta->getStatusCode());
        self::assertStringContainsString('Descrição aceita até 20 caracteres.', (string) $resposta->getBody());
        self::assertStringContainsString(sprintf('value="%s"', $descricao), (string) $resposta->getBody());
        self::assertSame([], $this->repositorio->listar());
    }

    public function testAlteraOAssuntoExistente(): void
    {
        $assunto = $this->repositorio->inserir(Assunto::novo('Cronica'));

        $resposta = $this->salvar((int) $assunto->codigo, 'Crônica');

        self::assertSame(303, $resposta->getStatusCode());
        self::assertSame('Crônica', $this->repositorio->buscar((int) $assunto->codigo)?->descricao);
        self::assertSame('Assunto Crônica alterado.', $this->sessao->obter('aviso'));
    }

    public function testAlterarAssuntoInexistenteRespondeNaoEncontrado(): void
    {
        self::assertSame(404, $this->salvar(999, 'Crônica')->getStatusCode());
    }

    public function testExcluiERedirecionaComAviso(): void
    {
        $assunto = $this->repositorio->inserir(Assunto::novo('Crônica'));

        $resposta = $this->excluir((int) $assunto->codigo);

        self::assertSame(303, $resposta->getStatusCode());
        self::assertNull($this->repositorio->buscar((int) $assunto->codigo));
        self::assertSame('Assunto Crônica excluído.', $this->sessao->obter('aviso'));
    }

    public function testExclusaoBloqueadaAbreOModalComOsLivrosNaLista(): void
    {
        $assunto = $this->repositorio->inserir(Assunto::novo('Crônica'));
        $this->repositorio->vincularLivros((int) $assunto->codigo, ['Agosto', 'Feliz Ano Novo']);

        $this->excluir((int) $assunto->codigo);
        $pagina = (string) $this->lista()->getBody();

        self::assertNotNull($this->repositorio->buscar((int) $assunto->codigo));
        self::assertStringContainsString('data-abrir-ao-carregar', $pagina);
        self::assertStringContainsString('O assunto Crônica está vinculado a 2 livros.', $pagina);
        self::assertStringContainsString('<li>Agosto</li>', $pagina);
        self::assertStringContainsString('<li>Feliz Ano Novo</li>', $pagina);
    }

    public function testExcluirAssuntoInexistenteRespondeNaoEncontrado(): void
    {
        self::assertSame(404, $this->excluir(999)->getStatusCode());
    }

    private function inserirAssuntosNumerados(int $quantidade): void
    {
        for ($numero = 1; $numero <= $quantidade; $numero++) {
            $this->repositorio->inserir(Assunto::novo(sprintf('Assunto %02d', $numero)));
        }
    }

    /**
     * @param array<string, string> $consulta
     */
    private function lista(array $consulta = []): ResponseInterface
    {
        return (new ListaDeAssuntosController($this->visao, $this->cadastro, $this->sessao, $this->formulario))
            ->handle((new ServerRequest('GET', '/assuntos'))->withQueryParams($consulta));
    }

    private function abrirFormulario(?int $codigo = null): ResponseInterface
    {
        $requisicao = new ServerRequest('GET', '/assuntos/novo');
        if ($codigo !== null) {
            $requisicao = $requisicao->withAttribute('codigo', (string) $codigo);
        }

        return (new FormularioDeAssuntoController($this->formulario, $this->cadastro))->handle($requisicao);
    }

    private function salvar(?int $codigo, string $descricao): ResponseInterface
    {
        $requisicao = (new ServerRequest('POST', '/assuntos'))->withParsedBody(['descricao' => $descricao]);
        if ($codigo !== null) {
            $requisicao = $requisicao->withAttribute('codigo', (string) $codigo);
        }

        return (new SalvarAssuntoController($this->formulario, $this->cadastro, $this->sessao, $this->visao))
            ->handle($requisicao);
    }

    private function excluir(int $codigo): ResponseInterface
    {
        $requisicao = (new ServerRequest('POST', '/assuntos/excluir'))->withAttribute('codigo', (string) $codigo);

        return (new ExcluirAssuntoController($this->formulario, $this->cadastro, $this->sessao, $this->visao))
            ->handle($requisicao);
    }
}
