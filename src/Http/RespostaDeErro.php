<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

use Psr\Http\Message\ResponseInterface;

interface RespostaDeErro
{
    public function responder(int $status, string $titulo, string $mensagem, string $orientacao): ResponseInterface;
}
