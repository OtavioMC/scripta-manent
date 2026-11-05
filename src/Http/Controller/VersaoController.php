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
        public string $versao,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->respostas->createResponse(501);
    }
}
