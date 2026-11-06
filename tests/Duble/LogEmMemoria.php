<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Duble;

use Psr\Log\AbstractLogger;
use Stringable;

final class LogEmMemoria extends AbstractLogger
{
    /** @var list<array{nivel: mixed, mensagem: string, contexto: array<mixed>}> */
    public array $registros = [];

    /**
     * @param array<mixed> $context
     */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->registros[] = ['nivel' => $level, 'mensagem' => (string) $message, 'contexto' => $context];
    }
}
