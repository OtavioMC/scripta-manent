<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Livro;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Livro\CadastroDeLivros;
use ScriptaManent\Domain\ErroDeValidacao;
use ScriptaManent\Http\Campos;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\Visao;

final readonly class SalvarLivroController implements RequestHandlerInterface
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
        $codigo = Campos::codigoDaRota($request);
        $campos = $this->tela->camposEnviados($request);
        $autores = Campos::codigos($request, 'autores');
        $assuntos = Campos::codigos($request, 'assuntos');

        if ($codigo !== null && $this->cadastro->buscar($codigo) === null) {
            return $this->tela->naoEncontrado($codigo);
        }

        try {
            $livro = $this->cadastro->salvar($codigo, $campos, $autores, $assuntos);
        } catch (ErroDeValidacao $erro) {
            return $this->tela->responder($codigo, $campos, $autores, $assuntos, $erro->erros, 422);
        }

        $this->sessao->guardar('aviso', sprintf(
            'Livro %s %s.',
            $livro->titulo,
            $codigo === null ? 'cadastrado' : 'alterado',
        ));

        return $this->visao->redirecionar('/livros');
    }
}
