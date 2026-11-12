<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class TraduzErrosDoBanco implements RequestHandlerInterface
{
    public function __construct(private RequestHandlerInterface $aplicacao)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->aplicacao->handle($request);
    }
}
