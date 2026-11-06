<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

interface Sessao
{
    public function obter(string $chave): mixed;

    public function guardar(string $chave, mixed $valor): void;

    public function retirar(string $chave): mixed;
}
