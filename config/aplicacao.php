<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use ScriptaManent\Http\Roteador;

return new Roteador(new Psr17Factory(), []);
