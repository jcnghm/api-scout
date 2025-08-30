<?php

namespace jcnghm\ApiScout\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use jcnghm\ApiScout\ApiScoutServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            ApiScoutServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Set up default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set up API Scout config
        $app['config']->set('api-scout', [
            'timeout' => 30,
            'connect_timeout' => 10,
            'endpoints' => [],
            'components' => [
                'generate_livewire' => true,
                'generate_blade' => true,
                'output_path' => 'app/Http/Livewire/ApiScout',
                'view_path' => 'resources/views/api-scout',
                'namespace' => 'App\\Http\\Livewire\\ApiScout',
            ],
            'type_detection' => [
                'sample_size' => 5,
                'strict_types' => false,
            ]
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test directories
        $this->createTestDirectories();
    }

    protected function createTestDirectories()
    {
        $directories = [
            app_path('Http/Livewire/ApiScout'),
            resource_path('views/api-scout'),
            resource_path('stubs/api-scout'),
        ];

        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $this->cleanupTestFiles();
        
        parent::tearDown();
    }

    protected function cleanupTestFiles()
    {
        $files = [
            app_path('Http/Livewire/ApiScout/TestComponent.php'),
            resource_path('views/api-scout/test-component.blade.php'),
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
