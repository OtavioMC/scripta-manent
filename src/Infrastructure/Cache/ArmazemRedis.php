<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Cache;

use Predis\ClientInterface;
use Predis\PredisException;

final readonly class ArmazemRedis implements Armazem
{
    public function __construct(private ClientInterface $redis)
    {
    }

    public function ler(string $chave): ?string
    {
        try {
            $valor = $this->redis->get($chave);
        } catch (PredisException $erro) {
            throw new ArmazemIndisponivel('O Redis não respondeu à leitura.', 0, $erro);
        }

        return is_string($valor) ? $valor : null;
    }

    public function gravar(string $chave, string $valor, int $segundos): void
    {
        try {
            $this->redis->setex($chave, $segundos, $valor);
        } catch (PredisException $erro) {
            throw new ArmazemIndisponivel('O Redis não respondeu à gravação.', 0, $erro);
        }
    }

    public function versoes(array $colecoes): array
    {
        try {
            $valores = $this->redis->mget(array_map(self::chaveDaVersao(...), $colecoes));
        } catch (PredisException $erro) {
            throw new ArmazemIndisponivel('O Redis não respondeu às versões.', 0, $erro);
        }

        $versoes = [];
        foreach ($colecoes as $indice => $colecao) {
            $valor = $valores[$indice] ?? null;
            $versoes[$colecao] = is_numeric($valor) ? (int) $valor : 0;
        }

        return $versoes;
    }

    public function incrementar(string $colecao): void
    {
        try {
            $this->redis->incr(self::chaveDaVersao($colecao));
        } catch (PredisException $erro) {
            throw new ArmazemIndisponivel('O Redis não respondeu ao incremento da versão.', 0, $erro);
        }
    }

    private static function chaveDaVersao(string $colecao): string
    {
        return 'versao:' . $colecao;
    }
}
