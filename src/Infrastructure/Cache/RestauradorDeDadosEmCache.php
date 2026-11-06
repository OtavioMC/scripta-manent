<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Cache;

use ScriptaManent\Application\Cache\VersoesDasColecoes;
use ScriptaManent\Application\DadosDeExemplo\RestauradorDeDados;

final readonly class RestauradorDeDadosEmCache implements RestauradorDeDados
{
    public function __construct(
        private RestauradorDeDados $restaurador,
        private LeituraEmCache $cache,
    ) {
    }

    public function restaurar(): void
    {
        $this->restaurador->restaurar();
        $this->cache->invalidar(...VersoesDasColecoes::COLECOES);
    }
}
