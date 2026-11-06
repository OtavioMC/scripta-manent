<?php

declare(strict_types=1);

namespace ScriptaManent\Http;

final readonly class TokenCsrf
{
    public const string CAMPO = '_csrf';
    private const string CHAVE_NA_SESSAO = 'csrf';

    public function __construct(private Sessao $sessao)
    {
    }

    public function valor(): string
    {
        $guardado = $this->sessao->obter(self::CHAVE_NA_SESSAO);
        if (is_string($guardado) && $guardado !== '') {
            return $guardado;
        }

        $novo = bin2hex(random_bytes(32));
        $this->sessao->guardar(self::CHAVE_NA_SESSAO, $novo);

        return $novo;
    }

    public function confere(string $enviado): bool
    {
        $guardado = $this->sessao->obter(self::CHAVE_NA_SESSAO);

        return is_string($guardado) && $guardado !== '' && hash_equals($guardado, $enviado);
    }

    public function campo(): string
    {
        return sprintf('<input type="hidden" name="%s" value="%s">', self::CAMPO, $this->valor());
    }
}
