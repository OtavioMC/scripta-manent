<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Domain\Livro;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Domain\ErroDeValidacao;
use ScriptaManent\Domain\Livro\Livro;

final class LivroTest extends TestCase
{
    public function testCriaOLivroComTodosOsDados(): void
    {
        $livro = Livro::criar(null, 'Dom Casmurro', 'Companhia das Letras', '2', '1899', '39,90', [1], [1, 2]);

        self::assertNull($livro->codigo);
        self::assertSame('Dom Casmurro', $livro->titulo);
        self::assertSame('Companhia das Letras', $livro->editora);
        self::assertSame(2, $livro->edicao);
        self::assertSame('1899', $livro->ano->valor);
        self::assertSame(3990, $livro->valor->centavos);
        self::assertSame([1], $livro->autores);
        self::assertSame([1, 2], $livro->assuntos);
    }

    public function testLivroPodeSerCriadoSemAutorESemAssunto(): void
    {
        $livro = Livro::criar(null, 'As Mil e Uma Noites', 'Globo', '1', '2005', '89,00', [], []);

        self::assertSame([], $livro->autores);
        self::assertSame([], $livro->assuntos);
    }

    public function testIgnoraAutoresEAssuntosRepetidos(): void
    {
        $livro = Livro::criar(null, 'Belas Maldições', 'Bertrand', '1', '2019', '54,90', [6, 5, 6], [5, 5]);

        self::assertSame([6, 5], $livro->autores);
        self::assertSame([5], $livro->assuntos);
    }

    public function testRemoveEspacosDasPontasDoTituloEDaEditora(): void
    {
        $livro = Livro::criar(null, ' Vidas Secas ', ' Record ', '1', '1938', '42,00', [], []);

        self::assertSame('Vidas Secas', $livro->titulo);
        self::assertSame('Record', $livro->editora);
    }

    public function testJuntaOsErrosDeTodosOsCampos(): void
    {
        self::assertSame([
            'titulo' => 'Informe o título do livro.',
            'editora' => 'Informe a editora do livro.',
            'edicao' => 'Informe a edição do livro.',
            'ano' => 'Informe o ano de publicação.',
            'valor' => 'Informe o valor do livro.',
        ], $this->errosAoCriar(titulo: '', editora: '', edicao: '', ano: '', valor: ''));
    }

    public function testRecusaTituloAcimaDoLimite(): void
    {
        self::assertSame(
            ['titulo' => 'Título aceita até 40 caracteres.'],
            $this->errosAoCriar(titulo: str_repeat('a', Livro::TITULO_MAXIMO + 1)),
        );
    }

    public function testAceitaTituloNoLimite(): void
    {
        $titulo = str_repeat('á', Livro::TITULO_MAXIMO);

        self::assertSame($titulo, Livro::criar(null, $titulo, 'Record', '1', '1938', '42,00', [], [])->titulo);
    }

    public function testRecusaEditoraAcimaDoLimite(): void
    {
        self::assertSame(
            ['editora' => 'Editora aceita até 40 caracteres.'],
            $this->errosAoCriar(editora: str_repeat('a', Livro::EDITORA_MAXIMA + 1)),
        );
    }

    public function testRecusaEdicaoZero(): void
    {
        self::assertSame(
            ['edicao' => 'Edição precisa ser um número inteiro de 1 a 999.'],
            $this->errosAoCriar(edicao: '0'),
        );
    }

    public function testRecusaEdicaoAcimaDoLimite(): void
    {
        self::assertSame(
            ['edicao' => 'Edição precisa ser um número inteiro de 1 a 999.'],
            $this->errosAoCriar(edicao: '1000'),
        );
    }

    public function testRecusaEdicaoComLetras(): void
    {
        self::assertSame(
            ['edicao' => 'Edição precisa ser um número inteiro de 1 a 999.'],
            $this->errosAoCriar(edicao: '2a'),
        );
    }

    public function testRecusaAnoEValorInvalidosJuntos(): void
    {
        self::assertSame([
            'ano' => 'Ano de publicação precisa ter 4 dígitos, como 1999.',
            'valor' => 'Valor precisa estar no formato 1.234,56.',
        ], $this->errosAoCriar(ano: '99', valor: '12.5'));
    }

    /**
     * @return array<string, string>
     */
    private function errosAoCriar(
        string $titulo = 'Vidas Secas',
        string $editora = 'Record',
        string $edicao = '1',
        string $ano = '1938',
        string $valor = '42,00',
    ): array {
        try {
            Livro::criar(null, $titulo, $editora, $edicao, $ano, $valor, [], []);
        } catch (ErroDeValidacao $erro) {
            return $erro->erros;
        }

        self::fail('O livro foi criado sem erro de validação.');
    }
}
