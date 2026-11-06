<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Http\Acesso;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Http\Acesso\LimiteDeTentativas;

final class LimiteDeTentativasTest extends TestCase
{
    private string $pasta;
    private int $agora = 1_000_000;

    protected function setUp(): void
    {
        $this->pasta = sys_get_temp_dir() . '/limite-teste-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->pasta . '/*.json') ?: [] as $arquivo) {
            unlink($arquivo);
        }
        if (is_dir($this->pasta)) {
            rmdir($this->pasta);
        }
    }

    public function testAsPrimeirasFalhasNaoBloqueiam(): void
    {
        $this->falhar(LimiteDeTentativas::FALHAS_LIVRES - 1);

        self::assertSame(0, $this->limite()->segundosDeEspera('203.0.113.7'));
    }

    public function testAQuintaFalhaBloqueiaPorTrintaSegundos(): void
    {
        $this->falhar(LimiteDeTentativas::FALHAS_LIVRES);

        self::assertSame(30, $this->limite()->segundosDeEspera('203.0.113.7'));
    }

    public function testCadaFalhaSeguidaDobraAEsperaAteQuinzeMinutos(): void
    {
        $this->falhar(LimiteDeTentativas::FALHAS_LIVRES + 1);
        self::assertSame(60, $this->limite()->segundosDeEspera('203.0.113.7'));

        $this->falhar(10);
        self::assertSame(900, $this->limite()->segundosDeEspera('203.0.113.7'));
    }

    public function testOBloqueioAcabaComOTempo(): void
    {
        $this->falhar(LimiteDeTentativas::FALHAS_LIVRES);
        $this->agora += 31;

        self::assertSame(0, $this->limite()->segundosDeEspera('203.0.113.7'));
    }

    public function testOrigensDiferentesTemContagensSeparadas(): void
    {
        $this->falhar(LimiteDeTentativas::FALHAS_LIVRES);

        self::assertSame(0, $this->limite()->segundosDeEspera('198.51.100.2'));
    }

    public function testLoginCertoLimpaAsFalhas(): void
    {
        $this->falhar(LimiteDeTentativas::FALHAS_LIVRES);

        $this->limite()->limpar('203.0.113.7');

        self::assertSame(0, $this->limite()->segundosDeEspera('203.0.113.7'));
    }

    private function falhar(int $vezes): void
    {
        for ($i = 0; $i < $vezes; $i++) {
            $this->limite()->registrarFalha('203.0.113.7');
        }
    }

    private function limite(): LimiteDeTentativas
    {
        return new LimiteDeTentativas($this->pasta, fn (): int => $this->agora);
    }
}
