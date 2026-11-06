<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final readonly class VerificadorDeCsrf implements RequestHandlerInterface
{
    private const array METODOS_SEGUROS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(
        private RequestHandlerInterface $aplicacao,
        private TokenCsrf $token,
        private RespostaDeErro $erros,
        private LoggerInterface $log,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (in_array($request->getMethod(), self::METODOS_SEGUROS, true)) {
            return $this->aplicacao->handle($request);
        }

        if ($this->token->confere(Campos::texto($request, TokenCsrf::CAMPO))) {
            return $this->aplicacao->handle($request);
        }

        $this->log->warning('Envio recusado sem token CSRF válido em {metodo} {caminho}', [
            'metodo' => $request->getMethod(),
            'caminho' => $request->getUri()->getPath(),
        ]);

        return $this->erros->responder(
            403,
            'Formulário recusado',
            'O formulário chegou sem a confirmação de segurança da página, ou ela expirou.',
            'Volte, recarregue a página e envie o formulário de novo.',
        );
    }
}
