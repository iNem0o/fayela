<?php

declare(strict_types = 1);

namespace Fayela\Http\Exception;

use Fayela\Http\StatusCode;
use RuntimeException;

abstract class AbstractHttpException extends RuntimeException
{
    abstract public function getStatusCode(): StatusCode;

    public function getBody(): string
    {
        return $this->getMessage();
    }
}
