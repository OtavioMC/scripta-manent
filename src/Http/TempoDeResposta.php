<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Cache\Medicoes;

final readonly class TempoDeResposta implements RequestHandlerInterface
{
    public function __construct(
        private RequestHandlerInterface $aplicacao,
        private Medicoes $medicoes,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $inicio = hrtime(true);
        $resposta = $this->aplicacao->handle($request);
        $total = (hrtime(true) - $inicio) / 1_000_000;

        unset($total);

        return $resposta->withHeader('Server-Timing', (string) count($this->medicoes->leituras()));
    }
}
