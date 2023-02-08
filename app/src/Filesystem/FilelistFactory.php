<?php

declare(strict_types = 1);

namespace Fayela\Filesystem;

use Fayela\Helper\HumanReadableConverter;
use FilesystemIterator;
use InvalidArgumentException;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class FilelistFactory
{
    protected string $startDirectory;

    /**
     * Public endpoint start path (without slash ending, ensured by the setter)
     */
    protected string $startPublicEndpoint;

    /**
     * Api endpoint start path (without slash ending, ensured by the setter)
     */
    protected string $startApiEndpoint;

    public function __construct(
        protected HumanReadableConverter $humanReadableConverter
    ) {
    }

    /**
     * Scan a directory and return the full subtree of files and directories
     *
     * @param string $realPath -   /src/data/folder/thefile.txt
     * @param string $shortPath -   /folder/thefile.txt
     * @param string $publicEndpoint -   http://127.0.0.1/folder/thefile.txt
     * @param int $lastCreatedFilesMaxCount -   10
     *
     * @return array<string, mixed>
     */
    public function scanDirectory(
        string $realPath,
        string $shortPath,
        string $publicEndpoint,
        int $lastCreatedFilesMaxCount = 10
    ): array {
        $breadcrumb = [
            [
                'href' => '/',
                'anchor' => 'Home',
            ],
        ];
        $breadcrumbCurrentPath = $this->startPublicEndpoint;
        $shortPathTraversable = array_filter(explode('/', $shortPath));
        $depth = count($shortPathTraversable);
        if ($depth > 0) {
            foreach ($shortPathTraversable as $subPath) {
                $breadcrumbCurrentPath .= '/' . urlencode($subPath);

                $breadcrumb[] = [
                    'href' => $breadcrumbCurrentPath . '/',
                    'anchor' => $subPath,
                ];
            }
        }

        $isRoot = 1 === count($breadcrumb);

        $contents = [
            'name' => pathinfo($realPath, PATHINFO_FILENAME),
            'public_path' => $publicEndpoint,
            'isRoot' => $isRoot,
            'isDir' => true,
            'isFile' => false,
            'timestamp_create' => filectime($realPath),
            'size' => 0,
            'children' => [],
            'children_total_files' => 0,
            'children_total_folders' => 0,
            'breadcrumb' => $breadcrumb,
            'last_created_files' => [],
        ];

        $dirs = scandir($realPath);
        if (false === $dirs) {
            throw new InvalidArgumentException(sprintf('Unable to scandir %s', $realPath), 500);
        }

        foreach ($dirs as $node) {
            if ('.' === $node || '..' === $node) {
                continue;
            }
            $file = new SplFileInfo($realPath . '/' . $node);

            if ($file->isDir()) {
                $publicPath = $this->createPublicPath($publicEndpoint, $file);

                $contents['children_total_folders']++;

                $folderStatistics = $this->getFolderStatistics($file->getRealPath(), $lastCreatedFilesMaxCount, $publicPath);
                $size = $folderStatistics['size'];
                $contents['size'] += $folderStatistics['size'];
                $contents['children_total_files'] += $folderStatistics['children_total_files'];
                $contents['children_total_folders'] += $folderStatistics['children_total_folders'];

                array_push($contents['last_created_files'], ...$folderStatistics['last_created_files']);

                $contents['children'][] = [
                    'isDir' => true,
                    'isFile' => false,

                    'name' => $file->getFilename(),
                    'path' => $file->getRealPath(),
                    'public_path' => $publicPath,
                    'size' => $size,
                    'size_human' => $this->humanReadableConverter->size($size),
                    'timestamp_create' => $file->getCTime(),

                    'children_total_files' => $folderStatistics['children_total_files'],
                    'children_total_folders' => $folderStatistics['children_total_folders'],
                    'last_created_files' => $folderStatistics['last_created_files'],
                ];
            } else {
                $size = $file->getSize();
                $contents['children_total_files']++;
                $contents['size'] += $size;
                $contents['children'][] = $this->createFile($file, $publicEndpoint);
            }

            if ($file->isFile()) {
                $contents['last_created_files'][] = $this->createFile($file, $publicEndpoint);
            }
        }
        $contents['size_human'] = $this->humanReadableConverter->size($contents['size']);
        $contents['last_created_files'] = $this->getLastCreatedFiles(
            $contents['last_created_files'],
            $lastCreatedFilesMaxCount
        );

        uasort(
            $contents['children'],
            static function ($a, $b) {
                return $b['isDir'] <=> $a['isDir'];
            }
        );

        return $contents;
    }

    public function getStartDirectory(): string
    {
        return $this->startDirectory;
    }

    public function setStartDirectory(string $startDirectory): void
    {
        $this->startDirectory = rtrim($startDirectory, '/');
    }

    public function setStartPublicEndpoint(string $startPublicEndpoint): void
    {
        $this->startPublicEndpoint = rtrim($startPublicEndpoint, '/');
        $this->startApiEndpoint = $this->startPublicEndpoint . '/api';
    }

    /**
     * @throws JsonException
     */
    public function scan(string $storagePath, ?callable $onProgress = null): void
    {
        $storagePath = rtrim($storagePath, '/');
        /** @var SplFileInfo $object */
        foreach (
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $this->getStartDirectory(),
                    FilesystemIterator::KEY_AS_PATHNAME
                )
            ) as $object
        ) {
            if (!$object->isDir()) {
                continue;
            }

            $filename = $object->getFilename();
            if ('..' === $filename) {
                continue;
            }

            $shortPath = ltrim(str_replace($this->getStartDirectory(), '', $object->getRealPath()), '/');
            if ('' === $shortPath) {
                $shortPath = '/';
            }

            $publicPath = $this->startPublicEndpoint . '/' . $shortPath;

            $directoriesJson = json_encode(
                $this->scanDirectory($object->getRealPath(), $shortPath, $publicPath),
                JSON_THROW_ON_ERROR
            );

            if ('/' !== $shortPath) {
                $shortPath = '/' . $shortPath;
            }
            file_put_contents($storagePath . '/' . md5($shortPath), $directoriesJson, LOCK_EX);
            if (is_callable($onProgress)) {
                $onProgress($shortPath);
            }
        }
    }

    /**
     * Create a data structure to represent a file in the list
     *
     * @return array{
     *     isDir: bool,
     *     isFile: bool,
     *     name: string,
     *     path: string,
     *     public_path: string,
     *     size: int,
     *     size_human: string,
     *     timestamp_create: int
     * }
     */
    public function createFile(string|SplFileInfo $fileInfo, string $publicEndpoint): array
    {
        if (!$fileInfo instanceof SplFileInfo) {
            $fileInfo = new SplFileInfo($fileInfo);
        }

        if (!$fileInfo->isFile()) {
            throw new InvalidArgumentException(sprintf('%s is not a file', $fileInfo->getRealPath()), 500);
        }

        $fileSize = $fileInfo->getSize();

        return [
            'isDir' => false,
            'isFile' => true,
            'name' => $fileInfo->getFilename(),
            'path' => (string)$fileInfo->getRealPath(),
            'public_path' => $this->createPublicPath($publicEndpoint, $fileInfo),
            'size' => (int)$fileSize,
            'size_human' => $this->humanReadableConverter->size($fileSize),
            'timestamp_create' => $fileInfo->getCTime(),
        ];
    }

    /**
     * Create a public path using a base $publicEndpoint and SpliFileInfo instance pointing to a file or a directory
     *
     * $fileInfo could be provided as a path to a file or a directory
     */
    protected function createPublicPath(string $publicEndpoint, SplFileInfo|string $fileInfo): string
    {
        if (is_string($fileInfo)) {
            $fileInfo = new SplFileInfo($fileInfo);
        }

        return sprintf(
            '%s/%s',
            rtrim($publicEndpoint, '/'),
            ltrim(urlencode($fileInfo->getFilename()), '/') . ($fileInfo->isDir() ? '/' : '')
        );
    }

    /**
     * Sort $filesCTimes by timestamp_create in descending order return result limited to $maxLastCreatedFiles
     *
     * @param array<int, array<string, bool|int|string>> $filesCTimes
     *
     * @return array<int, array<string, bool|int|string>>
     */
    protected function getLastCreatedFiles(array $filesCTimes, int $maxLastCreatedFiles = 10): array
    {
        usort($filesCTimes, static fn ($a, $b) => $b['timestamp_create'] <=> $a['timestamp_create']);
        if (count($filesCTimes) > $maxLastCreatedFiles) {
            $filesCTimes = array_slice($filesCTimes, 0, $maxLastCreatedFiles);
        }

        return $filesCTimes;
    }

    /**
     * @return array{
     *     'size': int,
     *     'children_total_files': int,
     *     'children_total_folders': int,
     *     'last_created_files': mixed
     *     }
     */
    private function getFolderStatistics(string $path, int $maxLastCreatedFiles, string $publicEndpoint): array
    {
        $path = rtrim($path, '/');

        $contents = [
            'size' => 0,
            'children_total_files' => 0,
            'children_total_folders' => 0,
            'last_created_files' => [],
        ];

        $files = glob(rtrim($path, '/') . '/*', GLOB_NOSORT);
        if (false === $files) {
            throw new InvalidArgumentException(sprintf('unable to read the files in %s', $path), 500);
        }

        // extract files and folder computing tree and size recursively
        $filesCTimes = [];
        foreach ($files as $item) {
            if (is_file($item)) {
                $contents['size'] += filesize($item);
                $contents['children_total_files']++;

                $filesCTimes[] = $this->createFile($item, $publicEndpoint);
            } else {
                $contents['children_total_folders']++;

                $subfolderData = $this->getFolderStatistics(
                    $item,
                    $maxLastCreatedFiles,
                    $this->createPublicPath($publicEndpoint, $item)
                );

                $contents['size'] += $subfolderData['size'];
                $contents['children_total_files'] += $subfolderData['children_total_files'];
                $contents['children_total_folders'] += $subfolderData['children_total_folders'];

                // merge subfolders recently added files
                array_push($filesCTimes, ...$subfolderData['last_created_files']);
            }
        }

        // sort and limit last created files
        if (count($filesCTimes) > 0) {
            $contents['last_created_files'] = $this->getLastCreatedFiles($filesCTimes, $maxLastCreatedFiles);
        }

        return $contents;
    }
}
