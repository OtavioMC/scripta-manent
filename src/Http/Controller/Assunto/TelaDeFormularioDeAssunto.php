<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Assunto;

use Psr\Http\Message\ResponseInterface;
use ScriptaManent\Domain\Assunto\Assunto;
use ScriptaManent\Http\RespostaDeErro;
use ScriptaManent\Http\Visao;

final readonly class TelaDeFormularioDeAssunto
{
    public function __construct(
        private Visao $visao,
        private RespostaDeErro $erros,
    ) {
    }

    /**
     * @param array<string, string> $erros
     */
    public function responder(?int $codigo, string $descricao, array $erros = [], int $status = 200): ResponseInterface
    {
        return $this->visao->responder(
            'assuntos/formulario.html.twig',
            $this->dados($codigo, $descricao, $erros),
            $status,
        );
    }

    /**
     * @param array<string, string> $erros
     * @return array<string, mixed>
     */
    public function dados(?int $codigo, string $descricao, array $erros = []): array
    {
        return [
            'editando' => $codigo !== null,
            'acao' => $codigo === null ? '/assuntos' : sprintf('/assuntos/%d', $codigo),
            'descricao' => $descricao,
            'erros' => $erros,
            'descricaoMaxima' => Assunto::DESCRICAO_MAXIMA,
            'descricaoObrigatoria' => Assunto::DESCRICAO_OBRIGATORIA,
        ];
    }

    public function naoEncontrado(int $codigo): ResponseInterface
    {
        return $this->erros->responder(
            404,
            'Assunto não encontrado',
            sprintf('Nenhum assunto cadastrado tem o código %d.', $codigo),
            'Volte para a lista de assuntos e escolha um assunto cadastrado.',
        );
    }
}
