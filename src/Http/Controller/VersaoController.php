<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class VersaoController implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $respostas,
        private string $versao,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $resposta = $this->respostas->createResponse(200)
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store');
        $resposta->getBody()->write($this->versao);

        return $resposta;
    }
}
