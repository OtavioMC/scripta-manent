<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class Roteador implements RequestHandlerInterface
{
    /**
     * @param list<array{string, string, RequestHandlerInterface}> $rotas
     */
    public function __construct(
        private ResponseFactoryInterface $respostas,
        public array $rotas,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->respostas->createResponse(501);
    }
}
