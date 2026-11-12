<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use ScriptaManent\Application\BancoIndisponivel;
use ScriptaManent\Application\GravacaoEmConflito;
use ScriptaManent\Http\RespostaDeErro;
use ScriptaManent\Http\TratadorDeErros;
use ScriptaManent\Tests\Duble\LogEmMemoria;
use Throwable;

final class TratadorDeErrosTest extends TestCase
{
    private LogEmMemoria $log;

    protected function setUp(): void
    {
        $this->log = new LogEmMemoria();
    }

    public function testRepassaARespostaDaAplicacaoQuandoNaoHaErro(): void
    {
        $resposta = $this->tratador($this->aplicacaoQueResponde('tudo certo'))
            ->handle(new ServerRequest('GET', '/'));

        self::assertSame(200, $resposta->getStatusCode());
        self::assertSame('tudo certo', (string) $resposta->getBody());
    }

    public function testRespondeErroInternoQuandoAAplicacaoFalha(): void
    {
        $resposta = $this->tratador($this->aplicacaoQueFalha(new RuntimeException('falha')))
            ->handle(new ServerRequest('GET', '/'));

        self::assertSame(500, $resposta->getStatusCode());
    }

    public function testPaginaDoErroInternoNaoMostraODetalheTecnico(): void
    {
        $resposta = $this->tratador($this->aplicacaoQueFalha(new RuntimeException('SQLSTATE[HY000] senha recusada')))
            ->handle(new ServerRequest('GET', '/'));

        self::assertStringNotContainsString('SQLSTATE', (string) $resposta->getBody());
    }

    public function testRegistraAFalhaNoLogComAExcecao(): void
    {
        $falha = new RuntimeException('falha');

        $this->tratador($this->aplicacaoQueFalha($falha))->handle(new ServerRequest('GET', '/'));

        self::assertCount(1, $this->log->registros);
        self::assertSame('error', $this->log->registros[0]['nivel']);
        self::assertSame($falha, $this->log->registros[0]['contexto']['exception'] ?? null);
    }

    public function testRegistroDizOMetodoEOEnderecoDaRequisicao(): void
    {
        $this->tratador($this->aplicacaoQueFalha(new RuntimeException('falha')))
            ->handle(new ServerRequest('POST', '/livros'));

        self::assertSame('POST', $this->log->registros[0]['contexto']['metodo'] ?? null);
        self::assertSame('/livros', $this->log->registros[0]['contexto']['caminho'] ?? null);
    }

    public function testBancoForaDoArRespondeIndisponivelSemDetalheTecnico(): void
    {
        $falha = new BancoIndisponivel('SQLSTATE[HY000] [2002] Connection refused');

        $resposta = $this->tratador($this->aplicacaoQueFalha($falha))->handle(new ServerRequest('GET', '/livros'));

        self::assertSame(503, $resposta->getStatusCode());
        self::assertStringContainsString('Sistema indisponível no momento', (string) $resposta->getBody());
        self::assertStringNotContainsString('SQLSTATE', (string) $resposta->getBody());
    }

    public function testBancoForaDoArFicaRegistradoComoCritico(): void
    {
        $falha = new BancoIndisponivel('Connection refused');

        $this->tratador($this->aplicacaoQueFalha($falha))->handle(new ServerRequest('GET', '/livros'));

        self::assertSame('critical', $this->log->registros[0]['nivel'] ?? null);
        self::assertSame($falha, $this->log->registros[0]['contexto']['exception'] ?? null);
    }

    public function testConflitoDeGravacaoDizQueNadaFoiSalvo(): void
    {
        $falha = new GravacaoEmConflito('SQLSTATE[40001]: Deadlock found when trying to get lock');

        $resposta = $this->tratador($this->aplicacaoQueFalha($falha))->handle(new ServerRequest('POST', '/livros'));

        self::assertSame(503, $resposta->getStatusCode());
        self::assertStringContainsString('Gravação não concluída', (string) $resposta->getBody());
        self::assertStringContainsString('Nada foi salvo', (string) $resposta->getBody());
        self::assertStringNotContainsString('SQLSTATE', (string) $resposta->getBody());
    }

    public function testConflitoDeGravacaoFicaRegistradoComoAviso(): void
    {
        $falha = new GravacaoEmConflito('Deadlock');

        $this->tratador($this->aplicacaoQueFalha($falha))->handle(new ServerRequest('POST', '/livros'));

        self::assertSame('warning', $this->log->registros[0]['nivel'] ?? null);
        self::assertSame($falha, $this->log->registros[0]['contexto']['exception'] ?? null);
    }

    private function tratador(RequestHandlerInterface $aplicacao): TratadorDeErros
    {
        return new TratadorDeErros($aplicacao, $this->respostaDeErroQueMostraOTexto(), $this->log);
    }

    private function aplicacaoQueResponde(string $corpo): RequestHandlerInterface
    {
        return new class ($corpo) implements RequestHandlerInterface {
            public function __construct(private readonly string $corpo)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], $this->corpo);
            }
        };
    }

    private function aplicacaoQueFalha(Throwable $falha): RequestHandlerInterface
    {
        return new class ($falha) implements RequestHandlerInterface {
            public function __construct(private readonly Throwable $falha)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw $this->falha;
            }
        };
    }

    private function respostaDeErroQueMostraOTexto(): RespostaDeErro
    {
        return new class implements RespostaDeErro {
            public function responder(
                int $status,
                string $titulo,
                string $mensagem,
                string $orientacao,
            ): ResponseInterface {
                return new Response($status, [], implode("\n", [$titulo, $mensagem, $orientacao]));
            }
        };
    }
}
