<?php

declare(strict_types=1);

namespace ScriptaManent\Domain\Livro;

final readonly class Livro
{
    public const int TITULO_MAXIMO = 40;
    public const int EDITORA_MAXIMA = 40;
    public const int EDICAO_MINIMA = 1;
    public const int EDICAO_MAXIMA = 999;
    public const string TITULO_OBRIGATORIO = 'Informe o título do livro.';
    public const string EDITORA_OBRIGATORIA = 'Informe a editora do livro.';
    public const string EDICAO_OBRIGATORIA = 'Informe a edição do livro.';
    public const string EDICAO_FORA_DO_LIMITE = 'Edição precisa ser um número inteiro de 1 a 999.';

    /**
     * @param list<int> $autores
     * @param list<int> $assuntos
     */
    private function __construct(
        public ?int $codigo,
        public string $titulo,
        public string $editora,
        public int $edicao,
        public AnoPublicacao $ano,
        public Dinheiro $valor,
        public array $autores,
        public array $assuntos,
    ) {
    }

    /**
     * @param list<int> $autores
     * @param list<int> $assuntos
     */
    public static function criar(
        ?int $codigo,
        string $titulo,
        string $editora,
        string $edicao,
        string $ano,
        string $valor,
        array $autores,
        array $assuntos,
    ): self {
        return new self(
            $codigo,
            $titulo,
            $editora,
            (int) $edicao,
            AnoPublicacao::deTexto($ano),
            Dinheiro::deTexto($valor),
            $autores,
            $assuntos,
        );
    }

    public function comCodigo(int $codigo): self
    {
        return new self(
            $codigo,
            $this->titulo,
            $this->editora,
            $this->edicao,
            $this->ano,
            $this->valor,
            $this->autores,
            $this->assuntos,
        );
    }
}
