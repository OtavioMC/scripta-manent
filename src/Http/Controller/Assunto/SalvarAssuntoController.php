<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Assunto;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Assunto\CadastroDeAssuntos;
use ScriptaManent\Domain\ErroDeValidacao;
use ScriptaManent\Http\Apresentador\FormularioDeAssuntoApresentador;
use ScriptaManent\Http\Campos;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\Visao;

final readonly class SalvarAssuntoController implements RequestHandlerInterface
{
    public function __construct(
        private FormularioDeAssuntoApresentador $tela,
        private CadastroDeAssuntos $cadastro,
        private Sessao $sessao,
        private Visao $visao,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $codigo = Campos::codigoDaRota($request);
        $descricao = Campos::texto($request, 'descricao');

        if ($codigo !== null && $this->cadastro->buscar($codigo) === null) {
            return $this->tela->naoEncontrado($codigo);
        }

        try {
            $assunto = $codigo === null
                ? $this->cadastro->cadastrar($descricao)
                : $this->cadastro->alterar($codigo, $descricao);
        } catch (ErroDeValidacao $erro) {
            return $this->tela->responder($codigo, $descricao, $erro->erros, 422);
        }

        $this->sessao->guardar('aviso', sprintf(
            'Assunto %s %s.',
            $assunto->descricao,
            $codigo === null ? 'cadastrado' : 'alterado',
        ));

        return $this->visao->redirecionar('/assuntos');
    }
}
