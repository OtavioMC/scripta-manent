<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Log;

use Psr\Log\AbstractLogger;
use Stringable;

final class LogEmArquivo extends AbstractLogger
{
    public function __construct(private readonly string $destino)
    {
    }

    /**
     * @param array<mixed> $context
     */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        file_put_contents($this->destino, $message . PHP_EOL, FILE_APPEND);
    }
}
