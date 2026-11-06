<?php

declare(strict_types=1);

namespace ScriptaManent\Application\Livro;

use ScriptaManent\Domain\Livro\Dinheiro;

final readonly class LivroNaLista
{
    /**
     * @param list<string> $autores
     * @param list<string> $assuntos
     * @param list<int> $codigosDosAutores
     * @param list<int> $codigosDosAssuntos
     */
    public function __construct(
        public int $codigo,
        public string $titulo,
        public string $editora,
        public int $edicao,
        public string $ano,
        public Dinheiro $valor,
        public array $autores,
        public array $assuntos,
        public array $codigosDosAutores,
        public array $codigosDosAssuntos,
    ) {
    }
}
