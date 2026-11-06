<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Cache;

interface Armazem
{
    /**
     * @throws ArmazemIndisponivel
     */
    public function ler(string $chave): ?string;

    /**
     * @throws ArmazemIndisponivel
     */
    public function gravar(string $chave, string $valor, int $segundos): void;

    /**
     * @param list<string> $colecoes
     * @return array<string, int>
     * @throws ArmazemIndisponivel
     */
    public function versoes(array $colecoes): array;

    /**
     * @throws ArmazemIndisponivel
     */
    public function incrementar(string $colecao): void;
}
