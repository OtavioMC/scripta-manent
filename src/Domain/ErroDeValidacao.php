<?php

declare(strict_types=1);

namespace ScriptaManent\Domain;

use DomainException;

final class ErroDeValidacao extends DomainException
{
    /**
     * @param array<string, string> $erros
     */
    public function __construct(public readonly array $erros)
    {
        parent::__construct(implode(' ', $erros));
    }
}
