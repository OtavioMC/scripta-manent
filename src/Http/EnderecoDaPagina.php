<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

use ScriptaManent\Application\Busca;

final class EnderecoDaPagina
{
    public static function de(string $lista, Busca $busca, int $numero): string
    {
        $consulta = [...($busca->vazia() ? [] : ['busca' => $busca->texto]), 'pagina' => $numero];

        return $lista . '?' . http_build_query($consulta, '', '&', PHP_QUERY_RFC3986);
    }
}
