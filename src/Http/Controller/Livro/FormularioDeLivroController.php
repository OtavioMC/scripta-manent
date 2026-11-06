<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Livro;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Livro\CadastroDeLivros;
use ScriptaManent\Http\Apresentador\FormularioDeLivroApresentador;
use ScriptaManent\Http\Campos;

final readonly class FormularioDeLivroController implements RequestHandlerInterface
{
    public function __construct(
        private FormularioDeLivroApresentador $tela,
        private CadastroDeLivros $cadastro,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $codigo = Campos::codigoDaRota($request);
        if ($codigo === null) {
            return $this->tela->vazio();
        }

        $livro = $this->cadastro->buscar($codigo);

        return $livro === null ? $this->tela->naoEncontrado($codigo) : $this->tela->doLivro($livro);
    }
}
