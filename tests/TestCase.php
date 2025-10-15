<?php

namespace Backstage\Mails\Tests;

use Backstage\Mails\MailsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Filesystem\Filesystem;
use NotificationChannels\Discord\DiscordServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{

    protected static array $migrations = [];

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn(string $modelName) => 'Backstage\\Mails\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
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
     * @param \Illuminate\Foundation\Application $app
     */
    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $app['config']->set('queue.default', 'sync');
    }

    /**
     * Load and run migrations from stub files
     */
    protected function loadMigrations(): void
    {
        $filesystem = new Filesystem();
        $migrationFiles = $filesystem->files(__DIR__ . '/../database/migrations/');

        // Sorting to ensure migrations run in the correct order
        usort($migrationFiles, function ($a, $b) {
            return strcmp($a->getFilename(), $b->getFilename());
        });

        foreach ($migrationFiles as $file) {
            // Skip if not a stub file
            if ($file->getExtension() !== 'stub') {
                continue;
            }

            $migration = include $file->getPathname();
            $migration->up();
        }
    }
}
