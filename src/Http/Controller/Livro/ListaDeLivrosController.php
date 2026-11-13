<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Livro;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Livro\CadastroDeLivros;
use ScriptaManent\Http\Apresentador\FormularioDeLivroApresentador;
use ScriptaManent\Http\Campos;
use ScriptaManent\Http\EnderecoDaPagina;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\Visao;

final readonly class ListaDeLivrosController implements RequestHandlerInterface
{
    public function __construct(
        private Visao $visao,
        private CadastroDeLivros $cadastro,
        private Sessao $sessao,
        private FormularioDeLivroApresentador $formulario,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $busca = Campos::busca($request);
        $pedida = Campos::pagina($request);
        $lista = $this->cadastro->pagina($busca, $pedida);
        if ($lista->pagina->numero !== $pedida->numero) {
            return $this->visao->redirecionar(EnderecoDaPagina::de('/livros', $busca, $lista->pagina->numero));
        }
        $aviso = $this->sessao->retirar('aviso');

        return $this->visao->responder('livros/lista.html.twig', [
            'lista' => $lista,
            'busca' => $busca->texto,
            'aviso' => is_string($aviso) ? $aviso : null,
            'formulario' => $this->formulario->dadosVazios(),
        ]);
    }
}
