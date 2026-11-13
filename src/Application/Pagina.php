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
        return new self((int) $texto);
    }

    public function deslocamento(): int
    {
        return 0;
    }

    public function limitadaA(int $total): self
    {
        return $this;
    }
}
