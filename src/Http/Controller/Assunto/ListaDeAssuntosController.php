<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Assunto;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScriptaManent\Application\Assunto\CadastroDeAssuntos;
use ScriptaManent\Http\Apresentador\FormularioDeAssuntoApresentador;
use ScriptaManent\Http\Campos;
use ScriptaManent\Http\Sessao;
use ScriptaManent\Http\Visao;

final readonly class ListaDeAssuntosController implements RequestHandlerInterface
{
    public function __construct(
        private Visao $visao,
        private CadastroDeAssuntos $cadastro,
        private Sessao $sessao,
        private FormularioDeAssuntoApresentador $formulario,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $aviso = $this->sessao->retirar('aviso');
        $busca = Campos::busca($request);

        return $this->visao->responder('assuntos/lista.html.twig', [
            'assuntos' => $this->cadastro->listar($busca),
            'busca' => $busca->texto,
            'aviso' => is_string($aviso) ? $aviso : null,
            'bloqueio' => $this->bloqueio($this->sessao->retirar('bloqueio')),
            'formulario' => $this->formulario->dados(null, ''),
        ]);
    }

    /**
     * @return array{mensagem: string, titulos: list<string>, busca: string}|null
     */
    private function bloqueio(mixed $guardado): ?array
    {
        $mensagem = is_array($guardado) ? $guardado['mensagem'] ?? null : null;
        $titulos = is_array($guardado) ? $guardado['titulos'] ?? null : null;
        $busca = is_array($guardado) ? $guardado['busca'] ?? '' : '';
        if (!is_string($mensagem) || !is_array($titulos)) {
            return null;
        }

        return [
            'mensagem' => $mensagem,
            'titulos' => array_values(array_filter($titulos, is_string(...))),
            'busca' => is_string($busca) ? $busca : '',
        ];
    }
}
