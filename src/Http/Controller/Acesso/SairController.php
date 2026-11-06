<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Acesso;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use ScriptaManent\Http\Acesso\SessaoDeAcesso;
use ScriptaManent\Http\Campos;
use ScriptaManent\Http\Visao;

final readonly class SairController implements RequestHandlerInterface
{
    public function __construct(
        private Visao $visao,
        private SessaoDeAcesso $acesso,
        private LoggerInterface $auditoria,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->acesso->sair();
        $this->auditoria->notice('Logout', ['origem' => Campos::origem($request)]);

        return $this->visao->redirecionar('/login?saiu=1');
    }
}
