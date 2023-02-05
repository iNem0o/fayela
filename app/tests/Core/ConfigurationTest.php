<?php

declare(strict_types = 1);

use Fayela\Core\Configuration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Fayela\Core\Configuration
 */
final class ConfigurationTest extends TestCase
{
    /**
     * @covers \Fayela\Core\Configuration::__construct
     */
    public function testDirectoriesPersonalisationReindexedByPath(): void
    {
        $config = new Configuration([
            'FAYELA__DIRECTORIES_PERSONALIZATION__0__PATH' => '/path1',
            'FAYELA__DIRECTORIES_PERSONALIZATION__0__NAME' => 'name path1',
            'FAYELA__DIRECTORIES_PERSONALIZATION__0__DESCRIPTION' => 'desc path1',
            'FAYELA__DIRECTORIES_PERSONALIZATION__1__PATH' => '/path2',
            'FAYELA__DIRECTORIES_PERSONALIZATION__1__NAME' => 'name path2',
            'FAYELA__DIRECTORIES_PERSONALIZATION__1__DESCRIPTION' => 'desc path2',
        ]);

        self::assertEquals(
            [
                '/path1' => [
                    'name' => 'name path1',
                    'description' => 'desc path1',
                    'path' => '/path1',
                ],
                '/path2' => [
                    'name' => 'name path2',
                    'description' => 'desc path2',
                    'path' => '/path2',
                ],
            ],
            $config['directories_personalization'],
            'FAYELA__DIRECTORIES_PERSONALIZATION'
        );
    }

    /**
     * @covers \Fayela\Core\Configuration::__construct
     * @covers \Fayela\Core\Configuration::getDefaultConfiguration
     */
    public function testDefaultConfiguration(): void
    {
        $config = new Configuration([
            'FAYELA__JSON_DATABASE_STORAGE_PATH' => '/test',
        ]);

        $class = new \ReflectionClass($config);
        $method = $class->getMethod('getDefaultConfiguration');
        $method->setAccessible(true);
        $defaultConfig = $method->invokeArgs($config, []);

        foreach ($defaultConfig as $k => $v) {
            if ('json_database_storage_path' === $k) {
                self::assertEquals(
                    '/test',
                    $config[$k],
                    sprintf('config "%s" should not be default value', $k)
                );
            } else {
                self::assertEquals(
                    $v,
                    $config[$k],
                    sprintf('default config setter %s', $k)
                );
            }
        }
    }

    /**
     * @covers \Fayela\Core\Configuration::__construct
     */
    public function testMalformedParseEnvVariables(): void
    {
        $vars = [
            'FAYELA__TEST' => true,
            'FAYELA__TEST__TEST' => true,
        ];

        self::expectException(\InvalidArgumentException::class);

        new Configuration($vars);
    }


    /**
     * @covers \Fayela\Core\Configuration::__construct
     */
    public function testParseEnvVariables(): void
    {
        $vars = [
            'FAYELA__TEST' => true,
            'FAYELA__NESTED__NESTED' => true,
            'FAYELA__NESTED2__NESTED2__NESTED2__NESTED2__NESTED2' => true,
            'FAYELA__ARRAY__0' => true,
            'FAYELA__ARRAY__1' => true,
            'FAYELA__ARRAY__2' => true,
            'FAYELA__NESTED_ARRAY__0__TEST' => true,
            'FAYELA__NESTED_ARRAY__0__TEST2' => true,
            'FAYELA__NESTED_ARRAY__1__TEST' => false,
            'FAYELA__NESTED_ARRAY__1__TEST2' => false,
        ];
        $config = new Configuration($vars);

        self::assertEquals(
            true,
            $config['test'],
            'FAYELA__TEST'
        );
        self::assertEquals(
            true,
            $config['nested']['nested'],
            'FAYELA__NESTED__NESTED'
        );
        self::assertEquals(
            true,
            $config['nested2']['nested2']['nested2']['nested2']['nested2'],
            'FAYELA__NESTED2__NESTED2__NESTED2__NESTED2__NESTED2'
        );
        self::assertEquals(
            [true, true, true],
            $config['array'],
            'FAYELA__ARRAY__[0,1,2]'
        );

        self::assertEquals(
            [
                [
                    'test' => true,
                    'test2' => true,
                ],
                [
                    'test' => false,
                    'test2' => false,
                ],
            ],
            $config['nested_array'],
            'FAYELA__NESTED_ARRAY__[0,1]__TEST[1,2]'
        );
    }

    /**
     * @covers \Fayela\Core\Configuration::offsetSet
     * @covers \Fayela\Core\Configuration::offsetUnset
     * @covers \Fayela\Core\Configuration::offsetExists
     */
    public function testArrayAccess(): void
    {
        $vars = [
            'FAYELA__TEST' => true,
        ];
        $config = new Configuration($vars);

        // test if exists
        self::assertTrue(
            isset($config['test'])
        );

        // ask delete, test it still exists
        unset($config['test']);
        self::assertTrue(
            isset($config['test'])
        );

        $config['test'] = false;
        self::assertTrue(
            $config['test']
        );
    }
}
