<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;

final readonly class Visao
{
    public function __construct(
        private Environment $twig,
        private ResponseFactoryInterface $respostas,
    ) {
    }

    /**
     * @param array<string, mixed> $dados
     */
    public function responder(string $template, array $dados = [], int $status = 200): ResponseInterface
    {
        $resposta = $this->respostas->createResponse($status)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
        $resposta->getBody()->write($this->twig->render($template, $dados));

        return $resposta;
    }

    public function redirecionar(string $endereco): ResponseInterface
    {
        return $this->respostas->createResponse(303)->withHeader('Location', $endereco);
    }
}
