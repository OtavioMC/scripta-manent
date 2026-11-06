<?php

declare(strict_types=1);

namespace ScriptaManent\Application\Relatorio;

use ScriptaManent\Domain\Relatorio\LinhaDoRelatorio;

interface FonteDoRelatorio
{
    /**
     * @return list<LinhaDoRelatorio>
     */
    public function linhas(): array;
}
