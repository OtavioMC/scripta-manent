<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Livro;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Livro\CadastroDeLivros;
use ScriptaManent\Http\Campos;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\Visao;

final readonly class ExcluirLivroController implements RequestHandlerInterface
{
    public function __construct(
        private TelaDeFormularioDeLivro $tela,
        private CadastroDeLivros $cadastro,
        private Sessao $sessao,
        private Visao $visao,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $codigo = Campos::codigoDaRota($request) ?? 0;
        $livro = $this->cadastro->buscar($codigo);
        if ($livro === null) {
            return $this->tela->naoEncontrado($codigo);
        }

        $this->cadastro->excluir($codigo);
        $this->sessao->guardar('aviso', sprintf('Livro %s excluído.', $livro->titulo));

        return $this->visao->redirecionar('/livros');
    }
}
