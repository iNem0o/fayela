<?php

declare(strict_types = 1);

namespace Fayela\Http\Exception;

use Fayela\Http\StatusCode;

class NotFoundHttpException extends AbstractHttpException
{
    public function getStatusCode(): StatusCode
    {
        return StatusCode::HTTP_404;
    }
}
