<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Http\RespostaDeErro;
use ScriptaManent\Http\TokenCsrf;
use ScriptaManent\Http\VerificadorDeCsrf;
use ScriptaManent\Tests\Duble\LogEmMemoria;
use ScriptaManent\Tests\Duble\SessaoEmMemoria;

final class VerificadorDeCsrfTest extends TestCase
{
    private TokenCsrf $token;
    private LogEmMemoria $log;

    protected function setUp(): void
    {
        $this->token = new TokenCsrf(new SessaoEmMemoria());
        $this->log = new LogEmMemoria();
    }

    public function testLeituraPassaSemToken(): void
    {
        self::assertSame(200, $this->verificar(new ServerRequest('GET', '/autores'))->getStatusCode());
    }

    public function testEnvioComOTokenDaSessaoPassa(): void
    {
        $requisicao = (new ServerRequest('POST', '/autores'))->withParsedBody(['_csrf' => $this->token->valor()]);

        self::assertSame(200, $this->verificar($requisicao)->getStatusCode());
    }

    public function testEnvioSemTokenERecusadoERegistrado(): void
    {
        $resposta = $this->verificar((new ServerRequest('POST', '/autores'))->withParsedBody(['nome' => 'X']));

        self::assertSame(403, $resposta->getStatusCode());
        self::assertSame('warning', $this->log->registros[0]['nivel'] ?? null);
    }

    public function testEnvioComTokenErradoERecusado(): void
    {
        $this->token->valor();
        $requisicao = (new ServerRequest('POST', '/autores'))->withParsedBody(['_csrf' => str_repeat('b', 64)]);

        self::assertSame(403, $this->verificar($requisicao)->getStatusCode());
    }

    private function verificar(ServerRequestInterface $requisicao): ResponseInterface
    {
        $aplicacao = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };
        $erros = new class implements RespostaDeErro {
            public function responder(
                int $status,
                string $titulo,
                string $mensagem,
                string $orientacao,
            ): ResponseInterface {
                return new Response($status, [], $mensagem);
            }
        };

        return (new VerificadorDeCsrf($aplicacao, $this->token, $erros, $this->log))->handle($requisicao);
    }
}
