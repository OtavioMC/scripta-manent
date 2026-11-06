<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Cache;

use JsonException;
use Psr\Log\LoggerInterface;
use ScriptaManent\Application\Cache\Medicoes;
use ScriptaManent\Application\Cache\VersoesDasColecoes;
use UnexpectedValueException;

final readonly class LeituraEmCache implements VersoesDasColecoes
{
    public function __construct(
        private Armazem $armazem,
        private Medicoes $medicoes,
        private LoggerInterface $log,
        private int $validadeEmSegundos,
    ) {
    }

    /**
     * @template T
     * @param callable(): T $buscar
     * @param callable(T): mixed $paraDados
     * @param callable(mixed): T $deDados
     * @return T
     */
    public function lembrar(
        string $colecao,
        string $consulta,
        callable $buscar,
        callable $paraDados,
        callable $deDados,
    ): mixed {
        $inicio = hrtime(true);
        try {
            $versao = $this->armazem->versoes([$colecao])[$colecao] ?? 0;
            $chave = sprintf('%s:v%d:%s', $colecao, $versao, hash('sha256', $consulta));
            $guardado = $this->armazem->ler($chave);
        } catch (ArmazemIndisponivel $erro) {
            $this->log->warning('Cache indisponível; leitura direto no banco', ['exception' => $erro]);

            return $this->doBanco($buscar, $inicio);
        }

        $resultado = $guardado === null ? null : $this->decodificar($guardado, $deDados);
        if ($resultado !== null) {
            $this->medicoes->registrar('cache', self::milissegundosDesde($inicio));

            return $resultado;
        }

        $resultado = $this->doBanco($buscar, $inicio);
        try {
            $this->armazem->gravar(
                $chave,
                json_encode($paraDados($resultado), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                $this->validadeEmSegundos,
            );
        } catch (ArmazemIndisponivel | JsonException $erro) {
            $this->log->warning('Resultado não guardado no cache', ['exception' => $erro]);
        }

        return $resultado;
    }

    public function invalidar(string ...$colecoes): void
    {
        foreach ($colecoes as $colecao) {
            try {
                $this->armazem->incrementar($colecao);
            } catch (ArmazemIndisponivel $erro) {
                $this->log->error('Versão da coleção {colecao} não incrementada', [
                    'colecao' => $colecao,
                    'exception' => $erro,
                ]);
            }
        }
    }

    public function atuais(): array
    {
        try {
            return $this->armazem->versoes(self::COLECOES);
        } catch (ArmazemIndisponivel $erro) {
            $this->log->warning('Versões das coleções indisponíveis', ['exception' => $erro]);

            return [];
        }
    }

    /**
     * @template T
     * @param callable(): T $buscar
     * @return T
     */
    private function doBanco(callable $buscar, int|float $inicio): mixed
    {
        $resultado = $buscar();
        $this->medicoes->registrar('banco', self::milissegundosDesde($inicio));

        return $resultado;
    }

    /**
     * @template T
     * @param callable(mixed): T $deDados
     * @return T|null
     */
    private function decodificar(string $guardado, callable $deDados): mixed
    {
        try {
            return $deDados(json_decode($guardado, true, 64, JSON_THROW_ON_ERROR));
        } catch (JsonException | UnexpectedValueException $erro) {
            $this->log->warning('Conteúdo inválido no cache; leitura refeita no banco', ['exception' => $erro]);

            return null;
        }
    }

    private static function milissegundosDesde(int|float $inicio): float
    {
        return (hrtime(true) - $inicio) / 1_000_000;
    }
}
