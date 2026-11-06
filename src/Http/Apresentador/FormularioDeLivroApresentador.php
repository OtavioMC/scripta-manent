<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Apresentador;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ScriptaManent\Application\Assunto\CadastroDeAssuntos;
use ScriptaManent\Application\Autor\CadastroDeAutores;
use ScriptaManent\Domain\Assunto\Assunto;
use ScriptaManent\Domain\Autor\Autor;
use ScriptaManent\Domain\Livro\AnoPublicacao;
use ScriptaManent\Domain\Livro\Dinheiro;
use ScriptaManent\Domain\Livro\Livro;
use ScriptaManent\Http\Campos;
use ScriptaManent\Http\RespostaDeErro;
use ScriptaManent\Http\Visao;

final readonly class FormularioDeLivroApresentador
{
    private const array CAMPOS_VAZIOS = ['titulo' => '', 'editora' => '', 'edicao' => '', 'ano' => '', 'valor' => ''];

    public function __construct(
        private Visao $visao,
        private RespostaDeErro $erros,
        private CadastroDeAutores $autores,
        private CadastroDeAssuntos $assuntos,
    ) {
    }

    /**
     * @param array{titulo: string, editora: string, edicao: string, ano: string, valor: string} $campos
     * @param list<int> $autoresMarcados
     * @param list<int> $assuntosMarcados
     * @param array<string, string> $erros
     */
    public function responder(
        ?int $codigo,
        array $campos,
        array $autoresMarcados,
        array $assuntosMarcados,
        array $erros = [],
        int $status = 200,
    ): ResponseInterface {
        return $this->visao->responder(
            'livros/formulario.html.twig',
            $this->dados($codigo, $campos, $autoresMarcados, $assuntosMarcados, $erros),
            $status,
        );
    }

    /**
     * @param array{titulo: string, editora: string, edicao: string, ano: string, valor: string} $campos
     * @param list<int> $autoresMarcados
     * @param list<int> $assuntosMarcados
     * @param array<string, string> $erros
     * @return array<string, mixed>
     */
    public function dados(
        ?int $codigo,
        array $campos,
        array $autoresMarcados,
        array $assuntosMarcados,
        array $erros = [],
    ): array {
        return [
            'editando' => $codigo !== null,
            'acao' => $codigo === null ? '/livros' : sprintf('/livros/%d', $codigo),
            'campos' => $campos,
            'erros' => $erros,
            'autores' => array_map(
                static fn (Autor $autor): array => ['codigo' => $autor->codigo, 'rotulo' => $autor->nome],
                $this->autores->listar(),
            ),
            'assuntos' => array_map(
                static fn (Assunto $assunto): array => ['codigo' => $assunto->codigo, 'rotulo' => $assunto->descricao],
                $this->assuntos->listar(),
            ),
            'autoresMarcados' => $autoresMarcados,
            'assuntosMarcados' => $assuntosMarcados,
            'limites' => [
                'titulo' => Livro::TITULO_MAXIMO,
                'editora' => Livro::EDITORA_MAXIMA,
                'edicaoMinima' => Livro::EDICAO_MINIMA,
                'edicaoMaxima' => Livro::EDICAO_MAXIMA,
                'valor' => strlen(Dinheiro::maximo()->paraCampo()),
            ],
            'mensagens' => [
                'titulo' => Livro::TITULO_OBRIGATORIO,
                'editora' => Livro::EDITORA_OBRIGATORIA,
                'edicao' => Livro::EDICAO_OBRIGATORIA,
                'edicaoFormato' => Livro::EDICAO_FORA_DO_LIMITE,
                'ano' => AnoPublicacao::ANO_OBRIGATORIO,
                'anoFormato' => AnoPublicacao::FORMATO,
                'valor' => Dinheiro::VALOR_OBRIGATORIO,
                'valorFormato' => Dinheiro::FORMATO,
            ],
        ];
    }

    public function vazio(): ResponseInterface
    {
        return $this->responder(null, self::CAMPOS_VAZIOS, [], []);
    }

    /**
     * @return array<string, mixed>
     */
    public function dadosVazios(): array
    {
        return $this->dados(null, self::CAMPOS_VAZIOS, [], []);
    }

    public function doLivro(Livro $livro): ResponseInterface
    {
        return $this->responder($livro->codigo, [
            'titulo' => $livro->titulo,
            'editora' => $livro->editora,
            'edicao' => (string) $livro->edicao,
            'ano' => $livro->ano->valor,
            'valor' => $livro->valor->paraCampo(),
        ], $livro->autores, $livro->assuntos);
    }

    /**
     * @return array{titulo: string, editora: string, edicao: string, ano: string, valor: string}
     */
    public function camposEnviados(ServerRequestInterface $request): array
    {
        return [
            'titulo' => Campos::texto($request, 'titulo'),
            'editora' => Campos::texto($request, 'editora'),
            'edicao' => Campos::texto($request, 'edicao'),
            'ano' => Campos::texto($request, 'ano'),
            'valor' => Campos::texto($request, 'valor'),
        ];
    }

    public function naoEncontrado(int $codigo): ResponseInterface
    {
        return $this->erros->responder(
            404,
            'Livro não encontrado',
            sprintf('Nenhum livro cadastrado tem o código %d.', $codigo),
            'Volte para a lista de livros e escolha um livro cadastrado.',
        );
    }
}
