<?php

declare(strict_types=1);

namespace ScriptaManent\Application;

final readonly class Pagina
{
    public const int ITENS = 20;

    private function __construct(public int $numero)
    {
    }

    public static function pedidaNaUrl(string $texto): self
    {
        if (!ctype_digit($texto)) {
            return new self(1);
        }

        return new self(max(1, min((int) $texto, intdiv(PHP_INT_MAX, self::ITENS))));
    }

    public function quantidadeDeItensAntes(): int
    {
        return ($this->numero - 1) * self::ITENS;
    }

    public static function quantidadeDePaginas(int $totalDeItens): int
    {
        return max(1, intdiv($totalDeItens + self::ITENS - 1, self::ITENS));
    }

    public function semPassarDaUltima(int $totalDeItens): self
    {
        return new self(min($this->numero, self::quantidadeDePaginas($totalDeItens)));
    }
}
