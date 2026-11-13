<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

use Psr\Http\Message\ServerRequestInterface;
use ScriptaManent\Application\Busca;
use ScriptaManent\Application\Pagina;

final class Campos
{
    public static function codigoDaRota(ServerRequestInterface $request): ?int
    {
        $codigo = $request->getAttribute('codigo');

        return is_string($codigo) && ctype_digit($codigo) ? (int) $codigo : null;
    }

    /**
     * @return list<int>
     */
    public static function codigos(ServerRequestInterface $request, string $nome): array
    {
        $corpo = $request->getParsedBody();
        $valores = is_array($corpo) ? $corpo[$nome] ?? [] : [];
        if (!is_array($valores)) {
            return [];
        }

        $validos = array_filter($valores, static fn (mixed $valor): bool => is_string($valor) && ctype_digit($valor));

        return array_values(array_map(intval(...), $validos));
    }

    public static function origem(ServerRequestInterface $request): string
    {
        $cloudflare = $request->getHeaderLine('CF-Connecting-IP');
        $endereco = $request->getServerParams()['REMOTE_ADDR'] ?? null;

        return $cloudflare !== '' ? $cloudflare : (is_string($endereco) ? $endereco : 'desconhecida');
    }

    public static function busca(ServerRequestInterface $request): Busca
    {
        $valor = $request->getQueryParams()['busca'] ?? '';

        return Busca::deTexto(is_string($valor) ? $valor : '');
    }

    public static function pagina(ServerRequestInterface $request): Pagina
    {
        $valor = $request->getQueryParams()['pagina'] ?? '';

        return Pagina::pedidaNaUrl(is_string($valor) ? $valor : '');
    }

    public static function texto(ServerRequestInterface $request, string $nome): string
    {
        $corpo = $request->getParsedBody();
        $valor = is_array($corpo) ? $corpo[$nome] ?? '' : '';

        return is_string($valor) ? $valor : '';
    }
}
