<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Http\Visao;

final readonly class InicioController implements RequestHandlerInterface
{
    public function __construct(private Visao $visao)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->visao->responder('inicio.html.twig');
    }
}
