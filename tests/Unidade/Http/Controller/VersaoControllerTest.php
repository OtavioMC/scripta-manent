<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http\Controller;

use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ScriptaManent\Http\Controller\VersaoController;

final class VersaoControllerTest extends TestCase
{
    public function testRespondeComStatus200(): void
    {
        self::assertSame(200, $this->responder('abc1234')->getStatusCode());
    }

    public function testCorpoTrazAVersao(): void
    {
        self::assertSame('abc1234', (string) $this->responder('abc1234')->getBody());
    }

    public function testRespondeEmTextoPuroUtf8(): void
    {
        self::assertSame(
            'text/plain; charset=utf-8',
            $this->responder('abc1234')->getHeaderLine('Content-Type'),
        );
    }

    public function testRespostaNaoEGuardadaEmCache(): void
    {
        self::assertSame('no-store', $this->responder('abc1234')->getHeaderLine('Cache-Control'));
    }

    public function testAceitaDevForaDoPipeline(): void
    {
        self::assertSame('dev', (string) $this->responder('dev')->getBody());
    }

    public function testAceitaHashCompletoDoCommit(): void
    {
        $hash = 'a6dab90c4e1f2b3a4d5c6e7f8091a2b3c4d5e6f7';

        self::assertSame($hash, (string) $this->responder($hash)->getBody());
    }

    #[DataProvider('versoesInvalidas')]
    public function testRecusaVersaoForaDoFormato(string $versao): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->responder($versao);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function versoesInvalidas(): iterable
    {
        yield 'vazia' => [''];
        yield 'hash curto demais' => ['abc12'];
        yield 'hash longo demais' => [str_repeat('a', 41)];
        yield 'letras maiúsculas' => ['ABC1234'];
        yield 'quebra de linha no fim' => ["abc1234\n"];
        yield 'número de versão' => ['1.0.0'];
    }

    private function responder(string $versao): ResponseInterface
    {
        $controller = new VersaoController(new Psr17Factory(), $versao);

        return $controller->handle(new ServerRequest('GET', '/versao'));
    }
}
