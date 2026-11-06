<?php

declare(strict_types=1);

namespace ScriptaManent\Tests\Duble;

use LogicException;
use ScriptaManent\Http\TokenCsrf;
use Twig\Environment;

final class TwigDeTeste
{
    public static function criar(): Environment
    {
        $twig = require __DIR__ . '/../../config/twig.php';
        if (!$twig instanceof Environment) {
            throw new LogicException('config/twig.php precisa devolver um Twig\Environment.');
        }
        $twig->addGlobal('csrf', new TokenCsrf(new SessaoEmMemoria()));

        return $twig;
    }
}
