<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Http\TokenCsrf;
use ScriptaManent\Tests\Duble\SessaoEmMemoria;

final class TokenCsrfTest extends TestCase
{
    public function testOMesmoTokenValeDuranteASessao(): void
    {
        $token = new TokenCsrf(new SessaoEmMemoria());

        self::assertSame($token->valor(), $token->valor());
        self::assertSame(64, strlen($token->valor()));
    }

    public function testConfereOTokenDaSessao(): void
    {
        $token = new TokenCsrf(new SessaoEmMemoria());

        self::assertTrue($token->confere($token->valor()));
    }

    public function testRecusaTokenDiferente(): void
    {
        $token = new TokenCsrf(new SessaoEmMemoria());
        $token->valor();

        self::assertFalse($token->confere(str_repeat('a', 64)));
    }

    public function testRecusaQualquerTokenQuandoASessaoNaoTemNenhum(): void
    {
        self::assertFalse((new TokenCsrf(new SessaoEmMemoria()))->confere(''));
    }

    public function testCampoOcultoLevaOTokenDaSessao(): void
    {
        $token = new TokenCsrf(new SessaoEmMemoria());

        self::assertSame(
            sprintf('<input type="hidden" name="_csrf" value="%s">', $token->valor()),
            $token->campo(),
        );
    }
}
