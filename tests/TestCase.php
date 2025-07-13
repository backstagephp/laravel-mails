<?php

namespace Backstage\Mails\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Filesystem\Filesystem;
use NotificationChannels\Discord\DiscordServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use SplFileInfo;
use Backstage\Mails\MailsServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Backstage\\Mails\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            DiscordServiceProvider::class,
            MailsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config([
            'database.default' => 'testing',
            'queue.default' => 'sync',
        ]);

        collect(app(Filesystem::class)->files(__DIR__.'/../database/migrations/'))
            ->map(fn (SplFileInfo $file) => require __DIR__.'/../database/migrations/'.$file->getBasename());
    }
}
