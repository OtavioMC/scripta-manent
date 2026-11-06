<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class TratadorDeErros implements RequestHandlerInterface
{
    public function __construct(
        private RequestHandlerInterface $aplicacao,
        private RespostaDeErro $erros,
        private LoggerInterface $log,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->aplicacao->handle($request);
        } catch (Throwable $erro) {
            $this->log->error('Falha ao responder {metodo} {caminho}', [
                'exception' => $erro,
                'metodo' => $request->getMethod(),
                'caminho' => $request->getUri()->getPath(),
            ]);

            return $this->erros->responder(
                500,
                'Erro interno',
                'A página não pôde ser carregada por causa de um erro no sistema. O erro foi registrado.',
                'Volte para a tela inicial e repita a ação. Se o erro continuar, avise quem cuida do sistema.',
            );
        }
    }
}
