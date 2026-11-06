<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Acesso;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Http\Acesso\SessaoDeAcesso;
use ScriptaManent\Http\Acesso\Situacao;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\Visao;

final readonly class TelaDeLoginController implements RequestHandlerInterface
{
    public function __construct(
        private Visao $visao,
        private SessaoDeAcesso $acesso,
        private Sessao $sessao,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->acesso->situacao() === Situacao::Autenticado) {
            return $this->visao->redirecionar('/');
        }

        $aviso = $this->sessao->retirar('aviso');
        $saiu = ($request->getQueryParams()['saiu'] ?? null) === '1';

        return $this->visao->responder('login.html.twig', [
            'usuario' => '',
            'erro' => null,
            'aviso' => is_string($aviso) ? $aviso : ($saiu ? 'Você saiu do sistema.' : null),
        ]);
    }
}
