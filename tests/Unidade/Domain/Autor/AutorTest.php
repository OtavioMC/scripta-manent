<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Domain\Autor;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Domain\Autor\Autor;
use ScriptaManent\Domain\ErroDeValidacao;

final class AutorTest extends TestCase
{
    public function testNovoAutorGuardaONomeESemCodigo(): void
    {
        $autor = Autor::novo('Machado de Assis');

        self::assertSame('Machado de Assis', $autor->nome);
        self::assertNull($autor->codigo);
    }

    public function testAutorExistenteGuardaOCodigo(): void
    {
        $autor = Autor::existente(7, 'Clarice Lispector');

        self::assertSame(7, $autor->codigo);
        self::assertSame('Clarice Lispector', $autor->nome);
    }

    public function testRemoveOsEspacosDoInicioEDoFimDoNome(): void
    {
        self::assertSame('Graciliano Ramos', Autor::novo('  Graciliano Ramos ')->nome);
    }

    public function testRecusaNomeVazio(): void
    {
        self::assertSame(['nome' => 'Informe o nome do autor.'], $this->errosAoCriar(''));
    }

    public function testRecusaNomeSoComEspacos(): void
    {
        self::assertSame(['nome' => 'Informe o nome do autor.'], $this->errosAoCriar('   '));
    }

    public function testAceitaNomeNoLimiteDeCaracteres(): void
    {
        $nome = str_repeat('a', Autor::NOME_MAXIMO);

        self::assertSame($nome, Autor::novo($nome)->nome);
    }

    public function testRecusaNomeAcimaDoLimiteDizendoOLimite(): void
    {
        $nome = str_repeat('a', Autor::NOME_MAXIMO + 1);

        self::assertSame(['nome' => 'Nome aceita até 40 caracteres.'], $this->errosAoCriar($nome));
    }

    public function testContaLetraAcentuadaComoUmCaractere(): void
    {
        $nome = str_repeat('é', Autor::NOME_MAXIMO);

        self::assertSame($nome, Autor::novo($nome)->nome);
    }

    public function testAutorExistenteTambemValidaONome(): void
    {
        $this->expectException(ErroDeValidacao::class);

        Autor::existente(7, '');
    }

    /**
     * @return array<string, string>
     */
    private function errosAoCriar(string $nome): array
    {
        try {
            Autor::novo($nome);
        } catch (ErroDeValidacao $erro) {
            return $erro->erros;
        }

        self::fail('O autor foi criado sem erro de validação.');
    }
}
