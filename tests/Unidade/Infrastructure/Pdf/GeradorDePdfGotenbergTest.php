<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Infrastructure\Pdf;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Application\Relatorio\PdfIndisponivel;
use ScriptaManent\Infrastructure\Pdf\GeradorDePdfGotenberg;

final class GeradorDePdfGotenbergTest extends TestCase
{
    public function testEnderecoVazioDesligaAGeracao(): void
    {
        self::assertFalse((new GeradorDePdfGotenberg('', 5))->ligado());
    }

    public function testComEnderecoAGeracaoFicaLigada(): void
    {
        self::assertTrue((new GeradorDePdfGotenberg('http://gotenberg:3000', 5))->ligado());
    }

    public function testGerarComAGeracaoDesligadaAvisaQueEstaIndisponivel(): void
    {
        $this->expectException(PdfIndisponivel::class);

        (new GeradorDePdfGotenberg('', 5))->gerar('<p>relatório</p>', []);
    }

    public function testServicoForaDoArViraPdfIndisponivel(): void
    {
        $this->expectException(PdfIndisponivel::class);

        (new GeradorDePdfGotenberg('http://127.0.0.1:9', 2))->gerar('<p>relatório</p>', []);
    }
}
