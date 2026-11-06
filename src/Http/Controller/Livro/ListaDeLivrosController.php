<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Livro;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Livro\CadastroDeLivros;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\Visao;

final readonly class ListaDeLivrosController implements RequestHandlerInterface
{
    public function __construct(
        private Visao $visao,
        private CadastroDeLivros $cadastro,
        private Sessao $sessao,
        private TelaDeFormularioDeLivro $formulario,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $aviso = $this->sessao->retirar('aviso');

        return $this->visao->responder('livros/lista.html.twig', [
            'livros' => $this->cadastro->listar(),
            'aviso' => is_string($aviso) ? $aviso : null,
            'formulario' => $this->formulario->dadosVazios(),
        ]);
    }
}
