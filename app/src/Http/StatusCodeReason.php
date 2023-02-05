<?php

declare(strict_types = 1);

namespace Fayela\Http;

enum StatusCodeReason: string
{
    case HTTP_100 = 'Continue';
    case HTTP_101 = 'Switching Protocols';
    case HTTP_200 = 'OK';
    case HTTP_201 = 'Created';
    case HTTP_202 = 'Accepted';
    case HTTP_203 = 'Non-Authoritative Information';
    case HTTP_204 = 'No Content';
    case HTTP_205 = 'Reset Content';
    case HTTP_206 = 'Partial Content';
    case HTTP_300 = 'Multiple Choices';
    case HTTP_301 = 'Moved Permanently';
    case HTTP_302 = 'Found';
    case HTTP_303 = 'See Other';
    case HTTP_304 = 'Not Modified';
    case HTTP_305 = 'Use Proxy';
    case HTTP_307 = 'Temporary Redirect';
    case HTTP_400 = 'Bad Request';
    case HTTP_401 = 'Unauthorized';
    case HTTP_402 = 'Payment Required';
    case HTTP_403 = 'Forbidden';
    case HTTP_404 = 'Not Found';
    case HTTP_405 = 'Method Not Allowed';
    case HTTP_406 = 'Not Acceptable';
    case HTTP_407 = 'Proxy Authentication Required';
    case HTTP_408 = 'Request Time-out';
    case HTTP_409 = 'Conflict';
    case HTTP_410 = 'Gone';
    case HTTP_411 = 'Length Required';
    case HTTP_412 = 'Precondition Failed';
    case HTTP_413 = 'Request Entity Too Large';
    case HTTP_414 = 'Request-URI Too Large';
    case HTTP_415 = 'Unsupported Media Type';
    case HTTP_416 = 'Requested Range Not Satisfiable';
    case HTTP_417 = 'Expectation Failed';
    case HTTP_500 = 'Internal Server Error';
    case HTTP_501 = 'Not Implemented';
    case HTTP_502 = 'Bad Gateway';
    case HTTP_503 = 'Service Unavailable';
    case HTTP_504 = 'Gateway Time-out';
    case HTTP_505 = 'HTTP Version Not Supported';
}
