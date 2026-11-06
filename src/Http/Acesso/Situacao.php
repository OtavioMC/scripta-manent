<?php

declare(strict_types=1);

namespace ScriptaManent\Http\Acesso;

enum Situacao
{
    case Anonimo;
    case Autenticado;
    case Expirado;
}
