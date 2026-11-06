<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Infrastructure\Cache;

use PHPUnit\Framework\TestCase;
use ScriptaManent\Application\Cache\Medicoes;
use ScriptaManent\Infrastructure\Cache\Dados;
use ScriptaManent\Infrastructure\Cache\LeituraEmCache;
use ScriptaManent\Tests\Duble\ArmazemEmMemoria;
use ScriptaManent\Tests\Duble\LogEmMemoria;

final class LeituraEmCacheTest extends TestCase
{
    private ArmazemEmMemoria $armazem;
    private Medicoes $medicoes;
    private LogEmMemoria $log;
    private LeituraEmCache $cache;
    private int $idasAoBanco = 0;

    protected function setUp(): void
    {
        $this->armazem = new ArmazemEmMemoria();
        $this->medicoes = new Medicoes();
        $this->log = new LogEmMemoria();
        $this->cache = new LeituraEmCache($this->armazem, $this->medicoes, $this->log, 300);
    }

    public function testASegundaLeituraVemDoCacheSemIrAoBanco(): void
    {
        $primeira = $this->ler('autores', '', ['Machado']);
        $segunda = $this->ler('autores', '', ['outro valor do banco']);

        self::assertSame(['Machado'], $primeira);
        self::assertSame(['Machado'], $segunda);
        self::assertSame(1, $this->idasAoBanco);
    }

    public function testResultadoVazioTambemFicaNoCache(): void
    {
        $this->ler('autores', 'zzz', []);
        $this->ler('autores', 'zzz', []);

        self::assertSame(1, $this->idasAoBanco);
    }

    public function testConsultasDiferentesNaoSeMisturam(): void
    {
        $this->ler('autores', 'machado', ['Machado']);

        self::assertSame(['Clarice'], $this->ler('autores', 'clarice', ['Clarice']));
    }

    public function testColecoesDiferentesNaoSeMisturam(): void
    {
        $this->ler('autores', '', ['Machado']);

        self::assertSame(['Romance'], $this->ler('assuntos', '', ['Romance']));
    }

    public function testInvalidarFazAProximaLeituraIrAoBanco(): void
    {
        $this->ler('autores', '', ['antigo']);

        $this->cache->invalidar('autores');

        self::assertSame(['novo'], $this->ler('autores', '', ['novo']));
    }

    public function testInvalidarUmaColecaoNaoAfetaAsOutras(): void
    {
        $this->ler('assuntos', '', ['Romance']);

        $this->cache->invalidar('autores');

        self::assertSame(['Romance'], $this->ler('assuntos', '', ['outro']));
    }

    public function testGuardaComAValidadeConfigurada(): void
    {
        $this->ler('autores', '', ['Machado']);

        self::assertSame([300], array_values($this->armazem->validades));
    }

    public function testMedeDeOndeVeioCadaLeitura(): void
    {
        $this->ler('autores', '', ['Machado']);
        $this->ler('autores', '', ['Machado']);

        self::assertSame(
            ['banco', 'cache'],
            array_map(static fn (array $leitura): string => $leitura['origem'], $this->medicoes->leituras()),
        );
    }

    public function testComORedisForaDoArLeDoBancoEAvisaNoLog(): void
    {
        $this->armazem->foraDoAr = true;

        self::assertSame(['Machado'], $this->ler('autores', '', ['Machado']));
        self::assertSame('warning', $this->log->registros[0]['nivel'] ?? null);
    }

    public function testConteudoInvalidoNoCacheEReler(): void
    {
        $this->ler('autores', '', ['Machado']);
        foreach (array_keys($this->armazem->guardados) as $chave) {
            $this->armazem->guardados[$chave] = '{"quebrado"';
        }

        self::assertSame(['do banco'], $this->ler('autores', '', ['do banco']));
    }

    public function testFormatoInesperadoNoCacheERelido(): void
    {
        $this->ler('autores', '', ['Machado']);
        foreach (array_keys($this->armazem->guardados) as $chave) {
            $this->armazem->guardados[$chave] = '{"nao":"lista"}';
        }

        self::assertSame(['do banco'], $this->ler('autores', '', ['do banco']));
    }

    public function testFalhaAoInvalidarNaoDerrubaAGravacaoERegistraErro(): void
    {
        $this->armazem->foraDoAr = true;

        $this->cache->invalidar('autores');

        self::assertSame('error', $this->log->registros[0]['nivel'] ?? null);
    }

    public function testInformaAsVersoesDasColecoes(): void
    {
        $this->cache->invalidar('autores');
        $this->cache->invalidar('autores');

        self::assertSame(2, $this->cache->atuais()['autores'] ?? null);
        self::assertSame(0, $this->cache->atuais()['livros'] ?? null);
    }

    public function testComORedisForaDoArNaoInformaVersoes(): void
    {
        $this->armazem->foraDoAr = true;

        self::assertSame([], $this->cache->atuais());
    }

    /**
     * @param list<string> $doBanco
     * @return list<string>
     */
    private function ler(string $colecao, string $consulta, array $doBanco): array
    {
        return $this->cache->lembrar(
            $colecao,
            $consulta,
            function () use ($doBanco): array {
                $this->idasAoBanco++;

                return $doBanco;
            },
            static fn (array $valores): array => ['valores' => $valores],
            static fn (mixed $dados): array => Dados::textos(Dados::registro($dados), 'valores'),
        );
    }
}
