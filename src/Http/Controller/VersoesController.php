<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Cache\VersoesDasColecoes;

final readonly class VersoesController implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $respostas,
        private VersoesDasColecoes $versoes,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $resposta = $this->respostas->createResponse(200)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store');
        $resposta->getBody()->write((string) count($this->versoes->atuais()));

        return $resposta;
    }
}
