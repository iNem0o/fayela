<?php

declare(strict_types = 1);

namespace Fayela;

use Fayela\Core\Configuration;
use Fayela\Helper\PhpfpmStatusReader;
use Fayela\Http\Exception\NotFoundHttpException;
use Fayela\Http\Exception\ServiceUnavailableHttpException;
use InvalidArgumentException;
use JsonException;

class Fayela
{
    protected Configuration $config;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $currentDirectory = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $currentFile = null;

    protected PhpfpmStatusReader $fpmStatus;


    public function __construct(
        Configuration $configuration,
        string $phpfpmDownloadPoolStatusUrl
    ) {
        $this->config = $configuration;

        $this->fpmStatus = new PhpfpmStatusReader($phpfpmDownloadPoolStatusUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDirectoryPersonalization(string $path): array
    {
        $localPath = str_replace(rtrim($this->getConfigString('data_directory'), '/'), '', $path);

        return $this->config['directories_personalization'][$localPath] ?? [];
    }

    public function getConfigString(string $key): string
    {
        return (string)($this->config[$key] ?? throw new InvalidArgumentException(sprintf('missing config %s', $key)));
    }

    public function getConfig(string $key): mixed
    {
        return $this->config[$key] ?? throw new InvalidArgumentException(sprintf('missing config %s', $key));
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getDownloadThreads(): array
    {
        $this->fpmStatus->load();

        return $this->fpmStatus->threads;
    }

    public function loadDatabase(string $currentRequestUri): void
    {
        // normalize request uri to find the directory database
        $parsedUriPath = (string)parse_url($currentRequestUri, PHP_URL_PATH);
        if ('/' !== $parsedUriPath) {
            $parsedUriPath = rtrim($parsedUriPath, '/');
        }
        $dirInfo = pathinfo($parsedUriPath);
        if (!isset($dirInfo['dirname'])) {
            throw new ServiceUnavailableHttpException('invalid request uri');
        }
        // search for directory database
        $databaseStorageDirectory = rtrim($this->getConfigString('json_database_storage_path'), '/');

        $dbFile = $databaseStorageDirectory . '/' . md5(urldecode($parsedUriPath));
        $isFile = false;
        if (!file_exists($dbFile)) {
            // no database found for directory, maybe we are on a file ?
            $dbFile = $databaseStorageDirectory . '/' . md5($dirInfo['dirname']);
            if (!file_exists($dbFile)) {
                throw new ServiceUnavailableHttpException('no database available');
            }
            $isFile = true;
        }

        try {
            $databaseData = json_decode((string)file_get_contents($dbFile), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($databaseData)) {
                throw new ServiceUnavailableHttpException('database not decoded');
            }
            $this->currentDirectory = $databaseData;

            if (true === $isFile) {
                $expectedFilename = urldecode($dirInfo['filename'] . (isset($dirInfo['extension']) ? '.' . $dirInfo['extension'] : ''));
                $searched = array_filter(
                    $databaseData['children'],
                    static fn ($row) => true === $row['isFile'] && $row['name'] === $expectedFilename
                );
                $searchCount = count($searched);

                if (1 === $searchCount) {
                    $this->currentFile = array_shift($searched);
                } elseif (0 === $searchCount) {
                    throw new NotFoundHttpException(
                        sprintf(
                            '%s not found',
                            $expectedFilename
                        )
                    );
                } else {
                    throw new ServiceUnavailableHttpException(
                        sprintf(
                            'multiple file found for the same %s url',
                            $expectedFilename
                        )
                    );
                }
            }
        } catch (JsonException $e) {
            throw new ServiceUnavailableHttpException('unable to read database', 0, $e);
        }
    }

    /**
     * @return ?array<string, mixed>
     */
    public function getCurrentDirectory(): ?array
    {
        return $this->currentDirectory;
    }

    /**
     * @return array<int, array{'href': ?string, 'anchor': string}>
     */
    public function getCurrentBreadcrumb(?string $sortBy = null, ?string $sortWay = null): array
    {
        if (null === $this->currentDirectory) {
            throw new NotFoundHttpException('no current folder');
        }
        if (isset($sortBy, $sortWay)) {
            $this->currentDirectory['breadcrumb'][] = [
                'href' => null,
                'anchor' => sprintf('Sort by %s %s', $sortBy, $sortWay),
            ];
        }

        if (isset($this->currentDirectory['breadcrumb'])) {
            $this->currentDirectory['breadcrumb'][count($this->currentDirectory['breadcrumb']) - 1]['href'] = null;
        }

        return $this->currentDirectory['breadcrumb'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCurrentFile(): ?array
    {
        return $this->currentFile;
    }

    /**
     * @param array<string, mixed> $directory
     *
     * @return array<string, mixed>
     */
    public function getDirectoryChildren(array $directory, ?string $currentSortBy = null, ?string $currentSortWay = null): array
    {
        $children = $directory['children'];

        if (null !== $currentSortBy && null !== $currentSortWay) {
            $sortCallback = match ($currentSortWay) {
                default => static fn ($a, $b) => $a[$currentSortBy] <=> $b[$currentSortBy],
                'desc' => static fn ($a, $b) => $b[$currentSortBy] <=> $a[$currentSortBy],
            };

            uasort($children, $sortCallback);
        }

        return $children;
    }
}
