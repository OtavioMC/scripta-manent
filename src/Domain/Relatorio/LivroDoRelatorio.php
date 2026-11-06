<?php

declare(strict_types=1);

namespace ScriptaManent\Domain\Relatorio;

use ScriptaManent\Domain\Livro\Dinheiro;

final readonly class LivroDoRelatorio
{
    public function __construct(
        public int $codigo,
        public string $titulo,
        public string $editora,
        public int $edicao,
        public string $ano,
        public Dinheiro $valor,
        public string $assuntos,
    ) {
    }

    public static function daLinha(LinhaDoRelatorio $linha): self
    {
        return new self(
            $linha->codigoDoLivro,
            $linha->titulo,
            $linha->editora,
            $linha->edicao,
            $linha->ano,
            $linha->valor,
            $linha->assuntos,
        );
    }
}
