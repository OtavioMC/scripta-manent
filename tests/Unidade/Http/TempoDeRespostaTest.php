<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Cache\Medicoes;
use ScriptaManent\Http\TempoDeResposta;

final class TempoDeRespostaTest extends TestCase
{
    public function testSemLeiturasInformaSoOTempoTotal(): void
    {
        $resposta = $this->responder(new Medicoes());

        self::assertMatchesRegularExpression('/^total;dur=\d+\.\d{2}$/', $resposta->getHeaderLine('Server-Timing'));
        self::assertSame('', $resposta->getHeaderLine('X-Cache'));
    }

    public function testLeituraDoCacheAparecePeloNomeDoRedisEMarcaHit(): void
    {
        $medicoes = new Medicoes();
        $medicoes->registrar('cache', 0.42);

        $resposta = $this->responder($medicoes);

        self::assertStringStartsWith(
            'cache1;desc="Redis";dur=0.42, total;dur=',
            $resposta->getHeaderLine('Server-Timing'),
        );
        self::assertSame('HIT', $resposta->getHeaderLine('X-Cache'));
    }

    public function testQualquerLeituraDoBancoMarcaMiss(): void
    {
        $medicoes = new Medicoes();
        $medicoes->registrar('cache', 0.42);
        $medicoes->registrar('banco', 8.1);

        $resposta = $this->responder($medicoes);

        self::assertStringContainsString('banco2;desc="MySQL";dur=8.10', $resposta->getHeaderLine('Server-Timing'));
        self::assertSame('MISS', $resposta->getHeaderLine('X-Cache'));
    }

    private function responder(Medicoes $medicoes): ResponseInterface
    {
        $aplicacao = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };

        return (new TempoDeResposta($aplicacao, $medicoes))->handle(new ServerRequest('GET', '/livros'));
    }
}
