<?php

declare(strict_types = 1);

use Fayela\Fayela;
use Fayela\Http\JsonResponse;
use Fayela\Http\StatusCode;

if (!isset($_SERVER['FAYELA__AUTHKEY'])) {
    header('HTTP/2.0 500 Internal Server Error');
    exit;
}

//if (!isset($_SERVER['HTTP_FAYELA_TOKEN'])) {
//    header('HTTP/2.0 500 Internal Server Error');
//    exit;
//}
//
//if (0 !== strcmp($_SERVER['HTTP_FAYELA_TOKEN'], $_SERVER['FAYELA__AUTHKEY'])) {
//    header('HTTP/2.0 401 Unauthorized');
//    exit;
//}

try {
    /** @var Fayela $app */
    $app = require __DIR__ . '/../bootstrap.php';

    $baseRequestUri = str_replace('/api/', '/', $_SERVER['REQUEST_URI'] ?? '');
    $app->loadDatabase($baseRequestUri);

    if (null !== $app->getCurrentDirectory()) {
        $response = new JsonResponse(StatusCode::HTTP_200);
        if (null !== $app->getCurrentFile()) {
            $response->setData($app->getCurrentFile());
        } else {
            $response->setData($app->getCurrentDirectory());
        }

        $response->send();
    }
} catch (Exception $e) {
    header('HTTP/2.0 500 Internal Server Error');
    error_log($e->getMessage());
    exit;
}
