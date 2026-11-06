<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Acesso;

final readonly class Credencial
{
    private const string HASH_DE_COMPARACAO = '$2y$12$sIj.jGjdtgy3KcizPTlgO.JBWtrCLi2VX6JCKFGuSfsIxUxHwzjbO';

    private function __construct(
        private string $usuario,
        private string $hash,
    ) {
    }

    public static function doFormatoHtpasswd(string $linha): self
    {
        $linha = trim($linha);
        $separador = strpos($linha, ':');
        if ($separador === false) {
            return new self('', '');
        }

        return new self(substr($linha, 0, $separador), substr($linha, $separador + 1));
    }

    public function ligada(): bool
    {
        return $this->usuario !== '' && $this->hash !== '';
    }

    public function confere(string $usuario, string $senha): bool
    {
        $usuarioConfere = hash_equals($this->usuario, $usuario);
        $senhaConfere = password_verify($senha, $this->ligada() ? $this->hash : self::HASH_DE_COMPARACAO);

        return $this->ligada() && $usuarioConfere && $senhaConfere;
    }
}
