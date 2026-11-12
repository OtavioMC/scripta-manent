<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Infrastructure\Banco;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PDOException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use ScriptaManent\Application\BancoIndisponivel;
use ScriptaManent\Application\GravacaoEmConflito;
use ScriptaManent\Infrastructure\Banco\TraduzErrosDoBanco;
use Throwable;

final class TraduzErrosDoBancoTest extends TestCase
{
    public function testConexaoPerdidaNoMeioDaOperacaoViraBancoIndisponivel(): void
    {
        $tradutor = new TraduzErrosDoBanco($this->aplicacaoQueFalha(self::erroDoMysql('HY000', 2006)));

        $this->expectException(BancoIndisponivel::class);

        $tradutor->handle(new ServerRequest('POST', '/livros'));
    }

    public function testConexaoInterrompidaDuranteAConsultaViraBancoIndisponivel(): void
    {
        $tradutor = new TraduzErrosDoBanco($this->aplicacaoQueFalha(self::erroDoMysql('HY000', 2013)));

        $this->expectException(BancoIndisponivel::class);

        $tradutor->handle(new ServerRequest('GET', '/relatorio'));
    }

    public function testDeadlockViraGravacaoEmConflito(): void
    {
        $tradutor = new TraduzErrosDoBanco($this->aplicacaoQueFalha(self::erroDoMysql('40001', 1213)));

        $this->expectException(GravacaoEmConflito::class);

        $tradutor->handle(new ServerRequest('POST', '/livros/3'));
    }

    public function testEsperaPorBloqueioEsgotadaViraGravacaoEmConflito(): void
    {
        $tradutor = new TraduzErrosDoBanco($this->aplicacaoQueFalha(self::erroDoMysql('HY000', 1205)));

        $this->expectException(GravacaoEmConflito::class);

        $tradutor->handle(new ServerRequest('POST', '/autores/1/excluir'));
    }

    public function testRepassaARespostaQuandoNaoHaErro(): void
    {
        $resposta = new Response(200, [], 'lista de livros');
        $tradutor = new TraduzErrosDoBanco($this->aplicacaoQueResponde($resposta));

        self::assertSame($resposta, $tradutor->handle(new ServerRequest('GET', '/livros')));
    }

    public function testGuardaOErroOriginalDoMysqlParaOLog(): void
    {
        $original = self::erroDoMysql('HY000', 2006);
        $tradutor = new TraduzErrosDoBanco($this->aplicacaoQueFalha($original));

        try {
            $tradutor->handle(new ServerRequest('GET', '/livros'));
            self::fail('A conexão perdida deveria virar BancoIndisponivel.');
        } catch (BancoIndisponivel $traduzido) {
            self::assertSame($original, $traduzido->getPrevious());
        }
    }

    public function testExclusaoBloqueadaPorChaveEstrangeiraSegueIntacta(): void
    {
        $original = self::erroDoMysql('23000', 1451);
        $tradutor = new TraduzErrosDoBanco($this->aplicacaoQueFalha($original));

        $this->expectExceptionObject($original);

        $tradutor->handle(new ServerRequest('POST', '/autores/1/excluir'));
    }

    public function testCodigoConhecidoComOutroSqlstateSegueIntacto(): void
    {
        $original = self::erroDoMysql('23000', 2006);
        $tradutor = new TraduzErrosDoBanco($this->aplicacaoQueFalha($original));

        $this->expectExceptionObject($original);

        $tradutor->handle(new ServerRequest('GET', '/livros'));
    }

    public function testErroDoPdoSemInformacaoDoMysqlSegueIntacto(): void
    {
        $original = new PDOException('falha sem errorInfo');
        $tradutor = new TraduzErrosDoBanco($this->aplicacaoQueFalha($original));

        $this->expectExceptionObject($original);

        $tradutor->handle(new ServerRequest('GET', '/livros'));
    }

    public function testErroQueNaoVemDoBancoSegueIntacto(): void
    {
        $original = new RuntimeException('falha no template');
        $tradutor = new TraduzErrosDoBanco($this->aplicacaoQueFalha($original));

        $this->expectExceptionObject($original);

        $tradutor->handle(new ServerRequest('GET', '/livros'));
    }

    private static function erroDoMysql(string $sqlstate, int $codigo): PDOException
    {
        $erro = new PDOException(sprintf('SQLSTATE[%s]: erro %d do MySQL', $sqlstate, $codigo));
        $erro->errorInfo = [$sqlstate, $codigo, 'mensagem do MySQL'];

        return $erro;
    }

    private function aplicacaoQueResponde(ResponseInterface $resposta): RequestHandlerInterface
    {
        return new class ($resposta) implements RequestHandlerInterface {
            public function __construct(private readonly ResponseInterface $resposta)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->resposta;
            }
        };
    }

    private function aplicacaoQueFalha(Throwable $falha): RequestHandlerInterface
    {
        return new class ($falha) implements RequestHandlerInterface {
            public function __construct(private readonly Throwable $falha)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw $this->falha;
            }
        };
    }
}
