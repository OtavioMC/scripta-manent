<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http\Acesso;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Http\Acesso\Credencial;

final class CredencialTest extends TestCase
{
    private static string $hash;

    public static function setUpBeforeClass(): void
    {
        self::$hash = password_hash('senha-certa', PASSWORD_BCRYPT, ['cost' => 4]);
    }

    public function testConfereUsuarioESenhaDoFormatoHtpasswd(): void
    {
        $credencial = Credencial::doFormatoHtpasswd('avaliador:' . self::$hash);

        self::assertTrue($credencial->confere('avaliador', 'senha-certa'));
    }

    public function testRecusaSenhaErrada(): void
    {
        $credencial = Credencial::doFormatoHtpasswd('avaliador:' . self::$hash);

        self::assertFalse($credencial->confere('avaliador', 'outra'));
    }

    public function testRecusaUsuarioErradoMesmoComASenhaCerta(): void
    {
        $credencial = Credencial::doFormatoHtpasswd('avaliador:' . self::$hash);

        self::assertFalse($credencial->confere('outro', 'senha-certa'));
    }

    public function testCredencialVaziaFicaDesligadaERecusaTudo(): void
    {
        $credencial = Credencial::doFormatoHtpasswd('');

        self::assertFalse($credencial->ligada());
        self::assertFalse($credencial->confere('', ''));
    }

    public function testLinhaSemSeparadorFicaDesligada(): void
    {
        self::assertFalse(Credencial::doFormatoHtpasswd('sem-separador')->ligada());
    }
}
