<?php

declare(strict_types = 1);

namespace Fayela\Helper;

use InvalidArgumentException;
use JsonException;

class PhpfpmStatusReader
{
    public int $activeThreadCount;

    public int $totalThreadCount;

    public int $currentQueueSize;

    public int $maxQueueSize;

    public int $maxActiveThreadCount;

    /**
     * @var array<int, array<string, string>>
     */
    public array $threads = [];

    protected string $statusUrl;

    public function __construct(
        string $statusUrl
    ) {
        $this->statusUrl = $statusUrl;
    }

    public function load(): void
    {
        try {
            $url = $this->statusUrl . '?full&json';
            $data = json_decode(
                (string)file_get_contents($url),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            throw new InvalidArgumentException(sprintf('unable to read the phpfpm status at %s', $url), 500, $e);
        }
        if (!is_array($data)) {
            throw new InvalidArgumentException(sprintf('unable to decode the phpfpm status at %s', $url), 500);
        }

        $this->activeThreadCount = (int)($data['active processes'] ?? 0);
        $this->totalThreadCount = (int)($data['total processes'] ?? 0);
        $this->currentQueueSize = (int)($data['listen queue'] ?? 0);
        $this->maxQueueSize = (int)($data['max listen queue'] ?? 0);
        $this->maxActiveThreadCount = (int)($data['max active processes'] ?? 0);

        $this->threads = [];
        foreach ($data['processes'] as $process) {
            $this->threads[] = [
                'state' => $process['state'],
                'user' => $process['user'],
            ];
        }
    }
}
