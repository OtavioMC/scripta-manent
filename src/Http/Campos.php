<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

use Psr\Http\Message\ServerRequestInterface;

final class Campos
{
    public static function codigoDaRota(ServerRequestInterface $request): ?int
    {
        $codigo = $request->getAttribute('codigo');

        return is_string($codigo) && ctype_digit($codigo) ? (int) $codigo : null;
    }

    public static function texto(ServerRequestInterface $request, string $nome): string
    {
        $corpo = $request->getParsedBody();
        $valor = is_array($corpo) ? $corpo[$nome] ?? '' : '';

        return is_string($valor) ? $valor : '';
    }
}
