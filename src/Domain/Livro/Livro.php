<?php

declare(strict_types=1);

namespace ScriptaManent\Domain\Livro;

use ScriptaManent\Domain\ErroDeValidacao;

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
        $erros = [];
        $titulo = self::texto($titulo, 'titulo', self::TITULO_OBRIGATORIO, self::TITULO_MAXIMO, 'Título', $erros);
        $editora = self::texto($editora, 'editora', self::EDITORA_OBRIGATORIA, self::EDITORA_MAXIMA, 'Editora', $erros);
        $edicao = self::edicao($edicao, $erros);
        $ano = self::valorOuErro(static fn (): AnoPublicacao => AnoPublicacao::deTexto($ano), $erros);
        $valor = self::valorOuErro(static fn (): Dinheiro => Dinheiro::deTexto($valor), $erros);

        if ($erros !== [] || $ano === null || $valor === null) {
            throw new ErroDeValidacao($erros);
        }

        return new self(
            $codigo,
            $titulo,
            $editora,
            $edicao,
            $ano,
            $valor,
            array_values(array_unique($autores)),
            array_values(array_unique($assuntos)),
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

    /**
     * @param array<string, string> $erros
     */
    private static function texto(
        string $valor,
        string $campo,
        string $obrigatorio,
        int $maximo,
        string $rotulo,
        array &$erros,
    ): string {
        $valor = trim($valor);

        if ($valor === '') {
            $erros[$campo] = $obrigatorio;
        } elseif (mb_strlen($valor) > $maximo) {
            $erros[$campo] = sprintf('%s aceita até %d caracteres.', $rotulo, $maximo);
        }

        return $valor;
    }

    /**
     * @param array<string, string> $erros
     */
    private static function edicao(string $edicao, array &$erros): int
    {
        $edicao = trim($edicao);

        if ($edicao === '') {
            $erros['edicao'] = self::EDICAO_OBRIGATORIA;

            return 0;
        }

        if (!ctype_digit($edicao) || (int) $edicao < self::EDICAO_MINIMA || (int) $edicao > self::EDICAO_MAXIMA) {
            $erros['edicao'] = self::EDICAO_FORA_DO_LIMITE;

            return 0;
        }

        return (int) $edicao;
    }

    /**
     * @template T of object
     * @param callable(): T $criar
     * @param array<string, string> $erros
     * @return T|null
     */
    private static function valorOuErro(callable $criar, array &$erros): ?object
    {
        try {
            return $criar();
        } catch (ErroDeValidacao $erro) {
            $erros = array_merge($erros, $erro->erros);

            return null;
        }
    }
}
