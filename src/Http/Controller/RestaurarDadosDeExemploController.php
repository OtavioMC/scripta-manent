<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use ScriptaManent\Application\DadosDeExemplo\RestauradorDeDados;
use ScriptaManent\Http\Campos;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\Visao;

final readonly class RestaurarDadosDeExemploController implements RequestHandlerInterface
{
    public function __construct(
        private RestauradorDeDados $restaurador,
        private Sessao $sessao,
        private Visao $visao,
        private LoggerInterface $auditoria,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->restaurador->restaurar();
        $this->auditoria->notice('Dados de exemplo restaurados', ['origem' => Campos::origem($request)]);
        $this->sessao->guardar(
            'aviso',
            'Os dados de exemplo foram restaurados. O que tinha sido cadastrado antes foi apagado.',
        );

        return $this->visao->redirecionar('/livros');
    }
}
