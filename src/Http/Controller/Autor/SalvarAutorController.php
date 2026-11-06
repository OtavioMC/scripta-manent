<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Autor;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Autor\CadastroDeAutores;
use ScriptaManent\Domain\ErroDeValidacao;
use ScriptaManent\Http\Apresentador\FormularioDeAutorApresentador;
use ScriptaManent\Http\Campos;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\Visao;

final readonly class SalvarAutorController implements RequestHandlerInterface
{
    public function __construct(
        private FormularioDeAutorApresentador $tela,
        private CadastroDeAutores $cadastro,
        private Sessao $sessao,
        private Visao $visao,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $codigo = Campos::codigoDaRota($request);
        $nome = Campos::texto($request, 'nome');

        if ($codigo !== null && $this->cadastro->buscar($codigo) === null) {
            return $this->tela->naoEncontrado($codigo);
        }

        try {
            $autor = $codigo === null
                ? $this->cadastro->cadastrar($nome)
                : $this->cadastro->alterar($codigo, $nome);
        } catch (ErroDeValidacao $erro) {
            return $this->tela->responder($codigo, $nome, $erro->erros, 422);
        }

        $this->sessao->guardar('aviso', sprintf(
            'Autor %s %s.',
            $autor->nome,
            $codigo === null ? 'cadastrado' : 'alterado',
        ));

        return $this->visao->redirecionar('/autores');
    }
}
