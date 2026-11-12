<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\BancoIndisponivel;
use ScriptaManent\Application\GravacaoEmConflito;

final readonly class TraduzErrosDoBanco implements RequestHandlerInterface
{
    public function __construct(private RequestHandlerInterface $aplicacao)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->aplicacao->handle($request);
        } catch (PDOException $erro) {
            if (ErroDoMysql::conexaoPerdida($erro)) {
                throw new BancoIndisponivel('A conexão com o MySQL caiu durante a operação.', 0, $erro);
            }
            if (ErroDoMysql::conflitoDeGravacao($erro)) {
                throw new GravacaoEmConflito('O MySQL desfez a transação por conflito com outra gravação.', 0, $erro);
            }

            throw $erro;
        }
    }
}
