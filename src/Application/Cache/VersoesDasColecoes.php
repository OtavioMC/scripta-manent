<?php

declare(strict_types=1);

namespace ScriptaManent\Application\Cache;

interface VersoesDasColecoes
{
    public const array COLECOES = ['livros', 'autores', 'assuntos', 'relatorio'];

    /**
     * @return array<string, int>
     */
    public function atuais(): array;
}
