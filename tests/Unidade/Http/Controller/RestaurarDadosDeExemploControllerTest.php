<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http\Controller;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ScriptaManent\Application\DadosDeExemplo\RestauradorDeDados;
use ScriptaManent\Http\Controller\RestaurarDadosDeExemploController;
use ScriptaManent\Http\Visao;
use ScriptaManent\Tests\Duble\LogEmMemoria;
use ScriptaManent\Tests\Duble\SessaoEmMemoria;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class RestaurarDadosDeExemploControllerTest extends TestCase
{
    private LogEmMemoria $auditoria;
    private SessaoEmMemoria $sessao;
    private int $restauracoes = 0;

    protected function setUp(): void
    {
        $this->auditoria = new LogEmMemoria();
        $this->sessao = new SessaoEmMemoria();
    }

    public function testRestauraERedirecionaParaALivrosComAviso(): void
    {
        $resposta = $this->restaurar(new ServerRequest('POST', '/dados-de-exemplo/restaurar'));

        self::assertSame(1, $this->restauracoes);
        self::assertSame(303, $resposta->getStatusCode());
        self::assertSame('/livros', $resposta->getHeaderLine('Location'));
        self::assertSame(
            'Os dados de exemplo foram restaurados. O que tinha sido cadastrado antes foi apagado.',
            $this->sessao->obter('aviso'),
        );
    }

    public function testRegistraARestauracaoNoLogDeAuditoriaComAOrigem(): void
    {
        $this->restaurar(
            (new ServerRequest('POST', '/dados-de-exemplo/restaurar'))->withHeader('CF-Connecting-IP', '203.0.113.7'),
        );

        self::assertCount(1, $this->auditoria->registros);
        self::assertSame('notice', $this->auditoria->registros[0]['nivel']);
        self::assertSame('Dados de exemplo restaurados', $this->auditoria->registros[0]['mensagem']);
        self::assertSame('203.0.113.7', $this->auditoria->registros[0]['contexto']['origem'] ?? null);
    }

    private function restaurar(ServerRequest $requisicao): ResponseInterface
    {
        $restaurador = new class ($this->restauracoes) implements RestauradorDeDados {
            public function __construct(private int &$restauracoes)
            {
            }

            public function restaurar(): void
            {
                $this->restauracoes++;
            }
        };
        $visao = new Visao(new Environment(new ArrayLoader()), new Psr17Factory());

        return (new RestaurarDadosDeExemploController($restaurador, $this->sessao, $visao, $this->auditoria))
            ->handle($requisicao);
    }
}
