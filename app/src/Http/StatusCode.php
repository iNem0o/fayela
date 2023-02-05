<?php

declare(strict_types = 1);

namespace Fayela\Http;

enum StatusCode: int
{
    case HTTP_100 = 100;
    case HTTP_101 = 101;
    case HTTP_200 = 200;
    case HTTP_201 = 201;
    case HTTP_202 = 202;
    case HTTP_203 = 203;
    case HTTP_204 = 204;
    case HTTP_205 = 205;
    case HTTP_206 = 206;
    case HTTP_300 = 300;
    case HTTP_301 = 301;
    case HTTP_302 = 302;
    case HTTP_303 = 303;
    case HTTP_304 = 304;
    case HTTP_305 = 305;
    case HTTP_307 = 307;
    case HTTP_400 = 400;
    case HTTP_401 = 401;
    case HTTP_402 = 402;
    case HTTP_403 = 403;
    case HTTP_404 = 404;
    case HTTP_405 = 405;
    case HTTP_406 = 406;
    case HTTP_407 = 407;
    case HTTP_408 = 408;
    case HTTP_409 = 409;
    case HTTP_410 = 410;
    case HTTP_411 = 411;
    case HTTP_412 = 412;
    case HTTP_413 = 413;
    case HTTP_414 = 414;
    case HTTP_415 = 415;
    case HTTP_416 = 416;
    case HTTP_417 = 417;
    case HTTP_500 = 500;
    case HTTP_501 = 501;
    case HTTP_502 = 502;
    case HTTP_503 = 503;
    case HTTP_504 = 504;
    case HTTP_505 = 505;
}
