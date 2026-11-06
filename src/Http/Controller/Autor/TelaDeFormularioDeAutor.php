<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Controller\Autor;

use Psr\Http\Message\ResponseInterface;
use ScriptaManent\Domain\Autor\Autor;
use ScriptaManent\Http\RespostaDeErro;
use ScriptaManent\Http\Visao;

final readonly class TelaDeFormularioDeAutor
{
    public function __construct(
        private Visao $visao,
        private RespostaDeErro $erros,
    ) {
    }

    /**
     * @param array<string, string> $erros
     */
    public function responder(?int $codigo, string $nome, array $erros = [], int $status = 200): ResponseInterface
    {
        return $this->visao->responder('autores/formulario.html.twig', $this->dados($codigo, $nome, $erros), $status);
    }

    /**
     * @param array<string, string> $erros
     * @return array<string, mixed>
     */
    public function dados(?int $codigo, string $nome, array $erros = []): array
    {
        return [
            'editando' => $codigo !== null,
            'acao' => $codigo === null ? '/autores' : sprintf('/autores/%d', $codigo),
            'nome' => $nome,
            'erros' => $erros,
            'nomeMaximo' => Autor::NOME_MAXIMO,
            'nomeObrigatorio' => Autor::NOME_OBRIGATORIO,
        ];
    }

    public function naoEncontrado(int $codigo): ResponseInterface
    {
        return $this->erros->responder(
            404,
            'Autor não encontrado',
            sprintf('Nenhum autor cadastrado tem o código %d.', $codigo),
            'Volte para a lista de autores e escolha um autor cadastrado.',
        );
    }
}
