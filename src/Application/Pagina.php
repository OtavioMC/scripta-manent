<?php

declare(strict_types=1);

namespace ScriptaManent\Application;

final readonly class Pagina
{
    public const int ITENS = 20;

    private function __construct(public int $numero)
    {
    }

    public static function daConsulta(string $texto): self
    {
        return new self(max(1, (int) $texto));
    }

    public function deslocamento(): int
    {
        return ($this->numero - 1) * self::ITENS;
    }

    public function limitadaA(int $total): self
    {
        $ultima = max(1, intdiv($total + self::ITENS - 1, self::ITENS));

        return new self(min($this->numero, $ultima));
    }
}
