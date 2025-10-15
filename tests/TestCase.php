<?php

namespace Backstage\Mails\Tests;

use Backstage\Mails\MailsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use NotificationChannels\Discord\DiscordServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected static array $migrations = [];

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Backstage\\Mails\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->loadMigrations();
    }

    protected function getPackageProviders($app): array
    {
        return [
            DiscordServiceProvider::class,
            MailsServiceProvider::class,
        ];
    }

    /**
     * Set up the environment for testing.
     *
     * @param  Application  $app
     */
    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $app['config']->set('queue.default', 'sync');
        
        // Disable Ray to avoid type compatibility issues
        $app['config']->set('ray.enabled', false);
    }

    /**
     * Load and run migrations from stub files
     */
    protected function loadMigrations(): void
    {
        $filesystem = new Filesystem;
        $migrationFiles = $filesystem->files(__DIR__.'/../database/migrations/');

        // Sorting to ensure migrations run in the correct order
        usort($migrationFiles, fn ($a, $b): int => strcmp((string) $a->getFilename(), (string) $b->getFilename()));

        foreach ($migrationFiles as $migrationFile) {
            // Skip if not a stub file
            if ($migrationFile->getExtension() !== 'stub') {
                continue;
            }

            $migration = include $migrationFile->getPathname();
            $migration->up();
        }
    }
}
