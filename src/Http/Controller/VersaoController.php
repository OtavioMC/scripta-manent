<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller;

use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class VersaoController implements RequestHandlerInterface
{
    private const string FORMATO = '/\A(dev|[0-9a-f]{7,40})\z/';

    public function __construct(
        private ResponseFactoryInterface $respostas,
        private string $versao,
    ) {
        if (preg_match(self::FORMATO, $versao) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'A versão precisa ser "dev" ou o hash do commit em letras minúsculas, '
                . 'com 7 a 40 caracteres. Veio "%s".',
                $versao,
            ));
        }
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
