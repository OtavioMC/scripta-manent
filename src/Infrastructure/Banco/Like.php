<?php

declare(strict_types=1);

namespace ScriptaManent\Infrastructure\Banco;

final class Like
{
    public static function contendo(string $texto): string
    {
        return '%' . addcslashes($texto, '\\%_') . '%';
    }
}
