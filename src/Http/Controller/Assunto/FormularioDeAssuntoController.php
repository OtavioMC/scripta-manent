<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Assunto;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Assunto\CadastroDeAssuntos;
use ScriptaManent\Http\Apresentador\FormularioDeAssuntoApresentador;
use ScriptaManent\Http\Campos;

final readonly class FormularioDeAssuntoController implements RequestHandlerInterface
{
    public function __construct(
        private FormularioDeAssuntoApresentador $tela,
        private CadastroDeAssuntos $cadastro,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $codigo = Campos::codigoDaRota($request);
        if ($codigo === null) {
            return $this->tela->responder(null, '');
        }

        $assunto = $this->cadastro->buscar($codigo);

        return $assunto === null
            ? $this->tela->naoEncontrado($codigo)
            : $this->tela->responder($codigo, $assunto->descricao);
    }
}
