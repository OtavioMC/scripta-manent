<?php

declare(strict_types=1);

namespace ScriptaManent\Application\Relatorio;

interface GeradorDePdf
{
    public function ligado(): bool;

    /**
     * @param array<string, string> $arquivos
     * @throws PdfIndisponivel
     */
    public function gerar(string $html, array $arquivos): string;
}
