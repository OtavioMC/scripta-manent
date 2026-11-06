<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Log;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;

final class LogEmArquivo extends AbstractLogger
{
    private const array NIVEIS = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];

    public function __construct(private readonly string $destino)
    {
    }

    /**
     * @param array<mixed> $context
     */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        if (!is_string($level) || !in_array($level, self::NIVEIS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Nível de log desconhecido: %s.',
                is_scalar($level) ? (string) $level : get_debug_type($level),
            ));
        }

        $excecao = $context['exception'] ?? null;
        unset($context['exception']);

        $registro = [
            'momento' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
            'nivel' => $level,
            'mensagem' => $this->substituirMarcadores((string) $message, $context),
            'contexto' => array_map($this->valorRegistravel(...), $context),
        ];
        if ($excecao instanceof Throwable) {
            $registro['excecao'] = [
                'classe' => $excecao::class,
                'mensagem' => $excecao->getMessage(),
                'arquivo' => $excecao->getFile(),
                'linha' => $excecao->getLine(),
                'pilha' => $excecao->getTraceAsString(),
            ];
        }

        $linha = json_encode($registro, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
            | JSON_PARTIAL_OUTPUT_ON_ERROR) . PHP_EOL;
        if (file_put_contents($this->destino, $linha, FILE_APPEND) === false) {
            error_log($linha);
        }
    }

    /**
     * @param array<mixed> $contexto
     */
    private function substituirMarcadores(string $mensagem, array $contexto): string
    {
        $trocas = [];
        foreach ($contexto as $chave => $valor) {
            if (is_scalar($valor) || $valor instanceof Stringable) {
                $trocas['{' . $chave . '}'] = (string) $valor;
            }
        }

        return strtr($mensagem, $trocas);
    }

    private function valorRegistravel(mixed $valor): mixed
    {
        return match (true) {
            $valor === null, is_scalar($valor) => $valor,
            $valor instanceof Stringable => (string) $valor,
            default => get_debug_type($valor),
        };
    }
}
