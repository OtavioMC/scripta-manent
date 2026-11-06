<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Domain\Livro;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ScriptaManent\Domain\ErroDeValidacao;
use ScriptaManent\Domain\Livro\Dinheiro;

final class DinheiroTest extends TestCase
{
    /**
     * @return iterable<string, array{string, int}>
     */
    public static function textosValidos(): iterable
    {
        yield 'com milhar e centavos' => ['1.234,56', 123456];
        yield 'sem milhar' => ['39,90', 3990];
        yield 'sem centavos' => ['45', 4500];
        yield 'com símbolo da moeda' => ['R$ 1.234,56', 123456];
        yield 'zero' => ['0,00', 0];
        yield 'no limite da coluna' => ['99.999.999,99', 9999999999];
    }

    #[DataProvider('textosValidos')]
    public function testLeOValorNoFormatoBrasileiro(string $texto, int $centavos): void
    {
        self::assertSame($centavos, Dinheiro::deTexto($texto)->centavos);
    }

    public function testRecusaValorVazio(): void
    {
        self::assertSame(['valor' => 'Informe o valor do livro.'], $this->errosAoLer(''));
    }

    public function testRecusaFormatoDiferenteDoBrasileiro(): void
    {
        self::assertSame(['valor' => 'Valor precisa estar no formato 1.234,56.'], $this->errosAoLer('12.5'));
    }

    public function testRecusaValorNegativo(): void
    {
        self::assertSame(['valor' => 'Valor precisa estar no formato 1.234,56.'], $this->errosAoLer('-10,00'));
    }

    public function testRecusaValorAcimaDoQueAColunaGuarda(): void
    {
        self::assertSame(['valor' => 'Valor aceita até R$ 99.999.999,99.'], $this->errosAoLer('100.000.000,00'));
    }

    public function testLeOValorGuardadoNoBanco(): void
    {
        self::assertSame(3990, Dinheiro::doBanco('39.90')->centavos);
    }

    public function testFormataParaExibicao(): void
    {
        self::assertSame('R$ 1.234,56', Dinheiro::deTexto('1234,56')->formatado());
    }

    public function testFormataParaOCampoDoFormulario(): void
    {
        self::assertSame('1.234,56', Dinheiro::deTexto('1234,56')->paraCampo());
    }

    public function testFormataParaOBanco(): void
    {
        self::assertSame('1234.56', Dinheiro::deTexto('1.234,56')->paraBanco());
    }

    /**
     * @return array<string, string>
     */
    private function errosAoLer(string $texto): array
    {
        try {
            Dinheiro::deTexto($texto);
        } catch (ErroDeValidacao $erro) {
            return $erro->erros;
        }

        self::fail('O valor foi aceito sem erro de validação.');
    }
}
