<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Unidade\Infrastructure\Banco;

use Nyholm\Psr7\ServerRequest;
use PDOException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\BancoIndisponivel;
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

    private static function erroDoMysql(string $sqlstate, int $codigo): PDOException
    {
        $erro = new PDOException(sprintf('SQLSTATE[%s]: erro %d do MySQL', $sqlstate, $codigo));
        $erro->errorInfo = [$sqlstate, $codigo, 'mensagem do MySQL'];

        return $erro;
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
