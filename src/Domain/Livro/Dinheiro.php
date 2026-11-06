<?php

declare(strict_types=1);

namespace ScriptaManent\Domain\Livro;

use ScriptaManent\Domain\ErroDeValidacao;

final readonly class Dinheiro
{
    public const int MAXIMO_EM_CENTAVOS = 9999999999;
    public const string VALOR_OBRIGATORIO = 'Informe o valor do livro.';
    public const string FORMATO = 'Valor precisa estar no formato 1.234,56.';
    private const string PADRAO = '/\A(\d{1,3}(\.\d{3})*|\d+)(,\d{2})?\z/';

    private function __construct(public int $centavos)
    {
    }

    public static function deTexto(string $texto): self
    {
        $texto = trim(str_replace('R$', '', $texto));

        if ($texto === '') {
            throw new ErroDeValidacao(['valor' => self::VALOR_OBRIGATORIO]);
        }

        if (preg_match(self::PADRAO, $texto) !== 1) {
            throw new ErroDeValidacao(['valor' => self::FORMATO]);
        }

        [$inteiro, $fracao] = array_pad(explode(',', str_replace('.', '', $texto)), 2, '00');
        $centavos = (int) $inteiro * 100 + (int) $fracao;

        if (strlen(ltrim($inteiro, '0')) > 8 || $centavos > self::MAXIMO_EM_CENTAVOS) {
            throw new ErroDeValidacao(['valor' => sprintf('Valor aceita até %s.', self::maximo()->formatado())]);
        }

        return new self($centavos);
    }

    public static function maximo(): self
    {
        return new self(self::MAXIMO_EM_CENTAVOS);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function deCentavos(int $centavos): self
    {
        if ($centavos < 0 || $centavos > self::MAXIMO_EM_CENTAVOS) {
            throw new ErroDeValidacao(['valor' => sprintf('Valor aceita até %s.', self::maximo()->formatado())]);
        }

        return new self($centavos);
    }

    public function mais(self $outro): self
    {
        return new self($this->centavos + $outro->centavos);
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
