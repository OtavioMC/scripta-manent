<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Integracao;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use ScriptaManent\Infrastructure\Banco\Conexao;
use ScriptaManent\Infrastructure\Banco\RestauradorDeDadosPdo;

final class RestauradorDeDadosPdoTest extends TestCase
{
    private const string SCRIPT = __DIR__ . '/../../banco/003-dados-exemplo.sql';

    protected function setUp(): void
    {
        ConexaoDeTeste::iniciarTransacao();
    }

    protected function tearDown(): void
    {
        ConexaoDeTeste::desfazerTransacao();
    }

    public function testApagaOQueFoiCadastradoERecarregaOsDadosDeExemplo(): void
    {
        $autor = ConexaoDeTeste::inserir('INSERT INTO Autor (Nome) VALUES (:nome)', ['nome' => 'Beatriz Lacerda']);

        $this->restaurador()->restaurar();

        self::assertSame([], ConexaoDeTeste::consultar('SELECT CodAu FROM Autor WHERE CodAu = :autor', [
            'autor' => $autor,
        ]));
        self::assertSame([['livros' => '62', 'autores' => '43', 'assuntos' => '13']], ConexaoDeTeste::consultar(
            'SELECT (SELECT COUNT(*) FROM Livro) AS livros, (SELECT COUNT(*) FROM Autor) AS autores,
                (SELECT COUNT(*) FROM Assunto) AS assuntos',
        ));
    }

    public function testRecarregaOsVinculosComOsCodigosDoScript(): void
    {
        $this->restaurador()->restaurar();

        self::assertSame([['Titulo' => 'Dom Casmurro', 'Nome' => 'Machado de Assis']], ConexaoDeTeste::consultar(
            'SELECT Livro.Titulo, Autor.Nome FROM Livro
             JOIN Livro_Autor ON Livro_Autor.Livro_Codl = Livro.Codl
             JOIN Autor ON Autor.CodAu = Livro_Autor.Autor_CodAu
             WHERE Livro.Codl = 1',
        ));
    }

    public function testFalhaComMensagemQuandoOScriptNaoExiste(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não pôde ser lido');

        (new RestauradorDeDadosPdo(new Conexao(ConexaoDeTeste::pdo(...)), '/nao/existe.sql'))->restaurar();
    }

    private function restaurador(): RestauradorDeDadosPdo
    {
        return new RestauradorDeDadosPdo(new Conexao(ConexaoDeTeste::pdo(...)), self::SCRIPT);
    }
}
