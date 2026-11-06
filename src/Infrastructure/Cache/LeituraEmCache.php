<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Cache;

use Psr\Log\LoggerInterface;
use ScriptaManent\Application\Cache\Medicoes;
use ScriptaManent\Application\Cache\VersoesDasColecoes;

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
        $resultado = $buscar();
        $this->medicoes->registrar('banco', 0.0);
        $this->armazem->gravar($colecao, (string) json_encode($paraDados($resultado)), $this->validadeEmSegundos);

        return $resultado;
    }

    public function invalidar(string ...$colecoes): void
    {
        foreach ($colecoes as $colecao) {
            $this->armazem->incrementar($colecao);
        }
        $this->log->debug('Coleções invalidadas');
    }

    public function atuais(): array
    {
        return $this->armazem->versoes(self::COLECOES);
    }
}
