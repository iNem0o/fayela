<?php

declare(strict_types = 1);

namespace Fayela\Http;

class Response
{
    public function __construct(
        protected StatusCode $statusCode,
        protected string $body = ''
    ) {
    }

    final public function send(): never
    {
        $this->sendHeaders();
        $this->sendBody();

        exit;
    }


    public function getStatusCode(): StatusCode
    {
        return $this->statusCode;
    }


    public function setStatusCode(StatusCode $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }


    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    protected function sendHeaders(): void
    {
        header(sprintf('HTTP/2.0 %s', $this->statusCode->value));
    }

    protected function sendBody(): void
    {
        echo $this->body;
    }
}
