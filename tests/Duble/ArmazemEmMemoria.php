<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Duble;

use ScriptaManent\Infrastructure\Cache\Armazem;
use ScriptaManent\Infrastructure\Cache\ArmazemIndisponivel;

final class ArmazemEmMemoria implements Armazem
{
    public bool $foraDoAr = false;

    /** @var array<string, string> */
    public array $guardados = [];

    /** @var array<string, int> */
    public array $validades = [];

    /** @var array<string, int> */
    private array $versoes = [];

    public function ler(string $chave): ?string
    {
        $this->exigirNoAr();

        return $this->guardados[$chave] ?? null;
    }

    public function gravar(string $chave, string $valor, int $segundos): void
    {
        $this->exigirNoAr();
        $this->guardados[$chave] = $valor;
        $this->validades[$chave] = $segundos;
    }

    public function versoes(array $colecoes): array
    {
        $this->exigirNoAr();
        $versoes = [];
        foreach ($colecoes as $colecao) {
            $versoes[$colecao] = $this->versoes[$colecao] ?? 0;
        }

        return $versoes;
    }

    public function incrementar(string $colecao): void
    {
        $this->exigirNoAr();
        $this->versoes[$colecao] = ($this->versoes[$colecao] ?? 0) + 1;
    }

    private function exigirNoAr(): void
    {
        if ($this->foraDoAr) {
            throw new ArmazemIndisponivel('Redis fora do ar no teste.');
        }
    }
}
