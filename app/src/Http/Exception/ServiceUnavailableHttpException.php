<?php

declare(strict_types = 1);

namespace Fayela\Http\Exception;

use Fayela\Http\StatusCode;

class ServiceUnavailableHttpException extends AbstractHttpException
{
    public function getStatusCode(): StatusCode
    {
        return StatusCode::HTTP_503;
    }
}
