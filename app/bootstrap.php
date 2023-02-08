<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Fayela\Core\Configuration;
use Fayela\Fayela;

function dd(): never
{
    var_dump(...func_get_args());
    exit;
}


return new Fayela(
    new Configuration(array_filter($_SERVER, static fn(string $key) => str_starts_with($key, 'FAYELA_'), ARRAY_FILTER_USE_KEY)),
    'http://localhost:8080/fpm-status'
);