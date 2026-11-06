<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Autor;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Autor\CadastroDeAutores;
use ScriptaManent\Http\Campos;

final readonly class FormularioDeAutorController implements RequestHandlerInterface
{
    public function __construct(
        private TelaDeFormularioDeAutor $tela,
        private CadastroDeAutores $cadastro,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $codigo = Campos::codigoDaRota($request);
        if ($codigo === null) {
            return $this->tela->responder(null, '');
        }

        $autor = $this->cadastro->buscar($codigo);

        return $autor === null ? $this->tela->naoEncontrado($codigo) : $this->tela->responder($codigo, $autor->nome);
    }
}
