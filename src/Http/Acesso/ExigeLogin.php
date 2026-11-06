<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Acesso;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\Visao;

final readonly class ExigeLogin implements RequestHandlerInterface
{
    private const array ROTAS_PUBLICAS = ['/login', '/versao'];

    public function __construct(
        private RequestHandlerInterface $aplicacao,
        private SessaoDeAcesso $acesso,
        private Sessao $sessao,
        private Visao $visao,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $caminho = $request->getUri()->getPath();
        if (in_array($caminho, self::ROTAS_PUBLICAS, true)) {
            return $this->aplicacao->handle($request);
        }

        $situacao = $this->acesso->situacao();
        if ($situacao === Situacao::Autenticado) {
            return $this->aplicacao->handle($request);
        }

        if ($request->getMethod() === 'GET' && str_contains($request->getHeaderLine('Accept'), 'text/html')) {
            $consulta = $request->getUri()->getQuery();
            $this->sessao->guardar('destino', $caminho . ($consulta === '' ? '' : '?' . $consulta));
        }
        if ($situacao === Situacao::Expirado) {
            $this->sessao->guardar(
                'aviso',
                'Sua sessão terminou depois de 2 horas sem uso. Entre de novo para continuar.',
            );
        }

        return $this->visao->redirecionar('/login');
    }
}
