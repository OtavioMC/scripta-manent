<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Domain\Livro;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Domain\ErroDeValidacao;
use ScriptaManent\Domain\Livro\AnoPublicacao;

final class AnoPublicacaoTest extends TestCase
{
    public function testAceitaAnoComQuatroDigitos(): void
    {
        self::assertSame('1899', AnoPublicacao::deTexto('1899')->valor);
    }

    public function testRemoveEspacosDasPontas(): void
    {
        self::assertSame('2016', AnoPublicacao::deTexto(' 2016 ')->valor);
    }

    public function testRecusaAnoVazio(): void
    {
        self::assertSame(['ano' => 'Informe o ano de publicação.'], $this->errosAoLer(''));
    }

    public function testRecusaAnoComTresDigitos(): void
    {
        self::assertSame(['ano' => 'Ano de publicação precisa ter 4 dígitos, como 1999.'], $this->errosAoLer('999'));
    }

    public function testRecusaAnoComLetras(): void
    {
        self::assertSame(['ano' => 'Ano de publicação precisa ter 4 dígitos, como 1999.'], $this->errosAoLer('19a9'));
    }

    /**
     * @return array<string, string>
     */
    private function errosAoLer(string $texto): array
    {
        try {
            AnoPublicacao::deTexto($texto);
        } catch (ErroDeValidacao $erro) {
            return $erro->erros;
        }

        self::fail('O ano foi aceito sem erro de validação.');
    }
}
