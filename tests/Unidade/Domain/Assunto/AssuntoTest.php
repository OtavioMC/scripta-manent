<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Domain\Assunto;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Domain\Assunto\Assunto;
use ScriptaManent\Domain\ErroDeValidacao;

final class AssuntoTest extends TestCase
{
    public function testNovoAssuntoGuardaADescricaoESemCodigo(): void
    {
        $assunto = Assunto::novo('Romance');

        self::assertSame('Romance', $assunto->descricao);
        self::assertNull($assunto->codigo);
    }

    public function testAssuntoExistenteGuardaOCodigo(): void
    {
        $assunto = Assunto::existente(3, 'Conto');

        self::assertSame(3, $assunto->codigo);
        self::assertSame('Conto', $assunto->descricao);
    }

    public function testRemoveOsEspacosDoInicioEDoFimDaDescricao(): void
    {
        self::assertSame('Poesia', Assunto::novo('  Poesia ')->descricao);
    }

    public function testRecusaDescricaoVazia(): void
    {
        self::assertSame(['descricao' => 'Informe a descrição do assunto.'], $this->errosAoCriar(''));
    }

    public function testRecusaDescricaoSoComEspacos(): void
    {
        self::assertSame(['descricao' => 'Informe a descrição do assunto.'], $this->errosAoCriar('   '));
    }

    public function testAceitaDescricaoNoLimiteDeCaracteres(): void
    {
        $descricao = str_repeat('a', Assunto::DESCRICAO_MAXIMA);

        self::assertSame($descricao, Assunto::novo($descricao)->descricao);
    }

    public function testRecusaDescricaoAcimaDoLimiteDizendoOLimite(): void
    {
        $descricao = str_repeat('a', Assunto::DESCRICAO_MAXIMA + 1);

        self::assertSame(['descricao' => 'Descrição aceita até 20 caracteres.'], $this->errosAoCriar($descricao));
    }

    public function testContaLetraAcentuadaComoUmCaractere(): void
    {
        $descricao = str_repeat('ç', Assunto::DESCRICAO_MAXIMA);

        self::assertSame($descricao, Assunto::novo($descricao)->descricao);
    }

    public function testAssuntoExistenteTambemValidaADescricao(): void
    {
        $this->expectException(ErroDeValidacao::class);

        Assunto::existente(3, '');
    }

    /**
     * @return array<string, string>
     */
    private function errosAoCriar(string $descricao): array
    {
        try {
            Assunto::novo($descricao);
        } catch (ErroDeValidacao $erro) {
            return $erro->erros;
        }

        self::fail('O assunto foi criado sem erro de validação.');
    }
}
