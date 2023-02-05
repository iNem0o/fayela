<?php

declare(strict_types = 1);

namespace Fayela\Http;

class JsonResponse extends Response
{
    /**
     * @var array<int|string, mixed>
     */
    protected array $data;

    public function sendHeaders(): void
    {
        parent::sendHeaders();
        header('Content-Type: application/json');
    }

    /**
     * @throws \JsonException
     */
    public function sendBody(): void
    {
        parent::sendBody();

        echo json_encode($this->data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    /**
     * @param array<int|string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
