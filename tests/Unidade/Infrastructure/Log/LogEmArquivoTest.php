<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Infrastructure\Log;

use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use RuntimeException;
use ScriptaManent\Infrastructure\Log\LogEmArquivo;

final class LogEmArquivoTest extends TestCase
{
    private string $arquivo;

    protected function setUp(): void
    {
        $arquivo = tempnam(sys_get_temp_dir(), 'log');
        self::assertIsString($arquivo);
        $this->arquivo = $arquivo;
    }

    protected function tearDown(): void
    {
        if (is_file($this->arquivo)) {
            unlink($this->arquivo);
        }
    }

    public function testGravaONivelEAMensagemEmJson(): void
    {
        $this->log()->warning('Banco fora do ar');

        $registro = $this->ultimoRegistro();
        self::assertSame('warning', $registro['nivel'] ?? null);
        self::assertSame('Banco fora do ar', $registro['mensagem'] ?? null);
    }

    public function testCadaRegistroOcupaUmaLinha(): void
    {
        $this->log()->info('primeiro');
        $this->log()->info('segundo');

        self::assertCount(2, $this->linhas());
    }

    public function testSubstituiOsMarcadoresPelosValoresDoContexto(): void
    {
        $this->log()->info('Livro {codl} gravado por {quem}', ['codl' => 7, 'quem' => 'admin']);

        self::assertSame('Livro 7 gravado por admin', $this->ultimoRegistro()['mensagem'] ?? null);
    }

    public function testRegistraAClasseEAMensagemDaExcecao(): void
    {
        $this->log()->error('Falha na requisição', ['exception' => new RuntimeException('conexão recusada')]);

        $excecao = $this->ultimoRegistro()['excecao'] ?? null;
        self::assertIsArray($excecao);
        self::assertSame(RuntimeException::class, $excecao['classe'] ?? null);
        self::assertSame('conexão recusada', $excecao['mensagem'] ?? null);
    }

    public function testGuardaOsDemaisValoresDoContexto(): void
    {
        $this->log()->error('Falha na requisição', ['metodo' => 'POST', 'caminho' => '/livros']);

        self::assertSame(['metodo' => 'POST', 'caminho' => '/livros'], $this->ultimoRegistro()['contexto'] ?? null);
    }

    public function testRegistraOMomentoEmUtc(): void
    {
        $this->log()->info('agora');

        $momento = $this->ultimoRegistro()['momento'] ?? null;
        self::assertIsString($momento);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $momento);
    }

    public function testRejeitaNivelDesconhecido(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->log()->log('grave', 'mensagem');
    }

    private function log(): LogEmArquivo
    {
        return new LogEmArquivo($this->arquivo);
    }

    /**
     * @return list<string>
     */
    private function linhas(): array
    {
        $conteudo = file_get_contents($this->arquivo);
        self::assertIsString($conteudo);

        $linhas = array_filter(explode(PHP_EOL, $conteudo), static fn (string $linha): bool => $linha !== '');

        return array_values($linhas);
    }

    /**
     * @return array<mixed>
     */
    private function ultimoRegistro(): array
    {
        $linhas = $this->linhas();
        $ultima = end($linhas);
        self::assertIsString($ultima);
        $registro = json_decode($ultima, true);
        self::assertIsArray($registro);

        return $registro;
    }
}
