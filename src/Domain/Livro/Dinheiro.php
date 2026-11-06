<?php

declare(strict_types=1);

namespace ScriptaManent\Domain\Livro;

final readonly class Dinheiro
{
    public const int MAXIMO_EM_CENTAVOS = 9999999999;
    public const string VALOR_OBRIGATORIO = 'Informe o valor do livro.';
    public const string FORMATO = 'Valor precisa estar no formato 1.234,56.';

    private function __construct(public int $centavos)
    {
    }

    public static function deTexto(string $texto): self
    {
        [$inteiro, $fracao] = array_pad(explode(',', str_replace('.', '', $texto)), 2, '00');

        return new self((int) $inteiro * 100 + (int) $fracao);
    }

    public static function maximo(): self
    {
        return new self(self::MAXIMO_EM_CENTAVOS);
    }

    public static function doBanco(string $decimal): self
    {
        [$inteiro, $fracao] = array_pad(explode('.', $decimal), 2, '00');

        return new self((int) $inteiro * 100 + (int) str_pad(substr($fracao, 0, 2), 2, '0'));
    }

    public function formatado(): string
    {
        return 'R$ ' . $this->paraCampo();
    }

    public function paraCampo(): string
    {
        return number_format(intdiv($this->centavos, 100), 0, '', '.') . ',' . $this->fracao();
    }

    public function paraBanco(): string
    {
        return intdiv($this->centavos, 100) . '.' . $this->fracao();
    }

    private function fracao(): string
    {
        return str_pad((string) ($this->centavos % 100), 2, '0', STR_PAD_LEFT);
    }
}
