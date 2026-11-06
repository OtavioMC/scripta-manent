<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Assunto;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Assunto\CadastroDeAssuntos;
use ScriptaManent\Domain\ExclusaoBloqueada;
use ScriptaManent\Http\Campos;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\Visao;

final readonly class ExcluirAssuntoController implements RequestHandlerInterface
{
    public function __construct(
        private TelaDeFormularioDeAssunto $tela,
        private CadastroDeAssuntos $cadastro,
        private Sessao $sessao,
        private Visao $visao,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $codigo = Campos::codigoDaRota($request) ?? 0;
        $assunto = $this->cadastro->buscar($codigo);
        if ($assunto === null) {
            return $this->tela->naoEncontrado($codigo);
        }

        try {
            $this->cadastro->excluir($codigo);
            $this->sessao->guardar('aviso', sprintf('Assunto %s excluído.', $assunto->descricao));
        } catch (ExclusaoBloqueada $bloqueio) {
            $this->sessao->guardar('bloqueio', [
                'mensagem' => $bloqueio->getMessage(),
                'titulos' => $bloqueio->titulos,
            ]);
        }

        return $this->visao->redirecionar('/assuntos');
    }
}
