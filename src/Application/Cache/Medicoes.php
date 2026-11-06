<?php

declare(strict_types=1);

namespace ScriptaManent\Application\Cache;

final class Medicoes
{
    /** @var list<array{origem: string, milissegundos: float}> */
    private array $leituras = [];

    public function registrar(string $origem, float $milissegundos): void
    {
        $this->leituras[] = ['origem' => $origem, 'milissegundos' => $milissegundos];
    }

    /**
     * @return list<array{origem: string, milissegundos: float}>
     */
    public function leituras(): array
    {
        return $this->leituras;
    }
}
