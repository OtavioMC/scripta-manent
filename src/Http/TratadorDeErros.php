<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use ScriptaManent\Application\BancoIndisponivel;
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
        } catch (BancoIndisponivel $erro) {
            $this->log->critical('Banco fora do ar em {metodo} {caminho}', [
                'exception' => $erro,
                'metodo' => $request->getMethod(),
                'caminho' => $request->getUri()->getPath(),
            ]);

            return $this->erros->responder(
                503,
                'Sistema indisponível no momento',
                'Os cadastros não puderam ser carregados agora.',
                'Espere alguns minutos e recarregue a página.',
            );
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
