<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

use Psr\Http\Message\ResponseInterface;

final readonly class PaginaDeErro implements RespostaDeErro
{
    public function __construct(private Visao $visao)
    {
    }

    public function responder(int $status, string $titulo, string $mensagem, string $orientacao): ResponseInterface
    {
        return $this->visao->responder('erro.html.twig', [
            'status' => $status,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'orientacao' => $orientacao,
        ], $status);
    }
}
