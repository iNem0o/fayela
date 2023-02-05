<?php

declare(strict_types = 1);

namespace Fayela\Core;

use ArrayAccess;
use InvalidArgumentException;

/**
 * @implements ArrayAccess<string, mixed>
 */
readonly class Configuration implements ArrayAccess
{
    /**
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * @param array<string, mixed> $envVariables
     */
    public function __construct(array $envVariables)
    {
        // extract from env
        $config = $this->extractConfigurationFromEnvVariables($envVariables);

        // TODO : add overide from a static php file

        // add mandatory values if missing
        $config += $this->getDefaultConfiguration();

        // reindex directories_personalization using path for later easy search
        if (count($config['directories_personalization']) > 0) {
            $config['directories_personalization'] = array_column($config['directories_personalization'], null, 'path');
        }

        $this->config = $config;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->config[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->config[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // nothing here as this is readonly configuration
    }

    public function offsetUnset(mixed $offset): void
    {
        // nothing here as this is readonly configuration
    }

    /**
     * Extract configuration from a variable container (like $_SERVER)
     *
     * @param iterable<string, mixed> $envContainer
     *
     * @return array<string, mixed>
     */
    protected function extractConfigurationFromEnvVariables(iterable $envContainer): array
    {
        $config = [];

        foreach ($envContainer as $k => $v) {
            if (str_starts_with($k, 'FAYELA_')) {
                // split variable name in parts, remove the first "FAYELA" and lowercase all the parts
                // FAYELA__PUBLIC_ENDPOINT  ==>  ["PUBLIC_ENDPOINT"]
                /** @var array<int, string> $parts */
                $parts = array_map(
                    static fn ($string) => mb_strtolower(trim($string)),
                    array_slice(explode('__', $k), 1)
                );
                $lastKey = count($parts) - 1;

                // iterate the parts and build the data traversing the config array
                $localConfig = &$config;
                foreach ($parts as $kPart => $part) {
                    if ($lastKey === $kPart) {
                        if (!is_array($localConfig)) {
                            throw new InvalidArgumentException(
                                sprintf(
                                    'malformed env variable ! %s is already defined and is not an array. ',
                                    $kPart
                                ),
                                500
                            );
                        }
                        $localConfig[$part] = $v;
                    } else {
                        if (!isset($localConfig[$part])) {
                            $localConfig[$part] = [];
                        }
                        $localConfig = &$localConfig[$part];
                    }
                }
                unset($localConfig);
            }
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDefaultConfiguration(): array
    {
        return [
            'directories_personalization' => [],
            'data_directory' => '/srv/data',
            'public_endpoint' => 'http://127.0.0.1:8080',
            'json_database_storage_path' => '/tmp/',
            'allowed_sort_by' => [
                'name',
                'size',
                'timestamp_create',
            ],
            'allowed_sort_way' => [
                'asc',
                'desc',
            ],
        ];
    }
}
