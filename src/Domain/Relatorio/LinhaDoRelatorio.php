<?php

declare(strict_types=1);

namespace ScriptaManent\Domain\Relatorio;

use ScriptaManent\Domain\Livro\Dinheiro;

final readonly class LinhaDoRelatorio
{
    public function __construct(
        public ?int $codigoDoAutor,
        public ?string $autor,
        public int $codigoDoLivro,
        public string $titulo,
        public string $editora,
        public int $edicao,
        public string $ano,
        public Dinheiro $valor,
        public string $assuntos,
    ) {
    }
}
