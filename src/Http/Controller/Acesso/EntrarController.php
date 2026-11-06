<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Acesso;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use ScriptaManent\Http\Acesso\Credencial;
use ScriptaManent\Http\Acesso\LimiteDeTentativas;
use ScriptaManent\Http\Acesso\SessaoDeAcesso;
use ScriptaManent\Http\Campos;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\Visao;

final readonly class EntrarController implements RequestHandlerInterface
{
    public function __construct(
        private Visao $visao,
        private Credencial $credencial,
        private SessaoDeAcesso $acesso,
        private Sessao $sessao,
        private LimiteDeTentativas $limite,
        private LoggerInterface $auditoria,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $origem = Campos::origem($request);
        $usuario = trim(Campos::texto($request, 'usuario'));

        $espera = $this->limite->segundosDeEspera($origem);
        if ($espera > 0) {
            $this->auditoria->warning('Login bloqueado por excesso de tentativas', ['origem' => $origem]);

            return $this->tela($usuario, sprintf(
                'Muitas tentativas seguidas. Espere %d %s antes de tentar de novo.',
                (int) ceil($espera / 60),
                $espera > 60 ? 'minutos' : 'minuto',
            ), 429);
        }

        if (!$this->credencial->confere($usuario, Campos::texto($request, 'senha'))) {
            $this->limite->registrarFalha($origem);
            $this->auditoria->warning('Falha de login', ['origem' => $origem, 'usuario' => $usuario]);

            return $this->tela($usuario, 'Usuário ou senha incorretos.', 401);
        }

        $this->limite->limpar($origem);
        $this->acesso->entrar($usuario);
        $this->auditoria->notice('Login realizado', ['origem' => $origem, 'usuario' => $usuario]);

        return $this->visao->redirecionar($this->destinoSeguro($this->sessao->retirar('destino')));
    }

    private function tela(string $usuario, string $erro, int $status): ResponseInterface
    {
        return $this->visao->responder('login.html.twig', [
            'usuario' => $usuario,
            'erro' => $erro,
            'aviso' => null,
        ], $status);
    }

    private function destinoSeguro(mixed $destino): string
    {
        $valido = is_string($destino)
            && str_starts_with($destino, '/')
            && !str_starts_with($destino, '//')
            && !str_contains($destino, '\\');

        return $valido ? $destino : '/';
    }
}
