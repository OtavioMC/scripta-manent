<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Autor;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Autor\CadastroDeAutores;
use ScriptaManent\Domain\ExclusaoBloqueada;
use ScriptaManent\Http\Campos;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\Visao;

final readonly class ExcluirAutorController implements RequestHandlerInterface
{
    public function __construct(
        private TelaDeFormularioDeAutor $tela,
        private CadastroDeAutores $cadastro,
        private Sessao $sessao,
        private Visao $visao,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $codigo = Campos::codigoDaRota($request) ?? 0;
        $autor = $this->cadastro->buscar($codigo);
        if ($autor === null) {
            return $this->tela->naoEncontrado($codigo);
        }

        try {
            $this->cadastro->excluir($codigo);
            $this->sessao->guardar('aviso', sprintf('Autor %s excluído.', $autor->nome));
        } catch (ExclusaoBloqueada $bloqueio) {
            $this->sessao->guardar('bloqueio', [
                'mensagem' => $bloqueio->getMessage(),
                'titulos' => $bloqueio->titulos,
            ]);
        }

        return $this->visao->redirecionar('/autores');
    }
}
