<?php

namespace Backstage\Mails;

use Backstage\Mails\Commands\CheckBounceRateCommand;
use Backstage\Mails\Commands\MonitorMailCommand;
use Backstage\Mails\Commands\PruneMailCommand;
use Backstage\Mails\Commands\ResendMailCommand;
use Backstage\Mails\Commands\WebhooksMailCommand;
use Backstage\Mails\Contracts\MailProviderContract;
use Backstage\Mails\Events\MailEvent;
use Backstage\Mails\Events\MailHardBounced;
use Backstage\Mails\Events\MailUnsuppressed;
use Backstage\Mails\Listeners\AttachMailLogUuid;
use Backstage\Mails\Listeners\LogMailEvent;
use Backstage\Mails\Listeners\LogSendingMail;
use Backstage\Mails\Listeners\LogSentMail;
use Backstage\Mails\Listeners\NotifyOnBounce;
use Backstage\Mails\Listeners\StoreMailRelations;
use Backstage\Mails\Listeners\UnsuppressEmailAddress;
use Backstage\Mails\Managers\MailProviderManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use SplFileInfo;

class MailsServiceProvider extends PackageServiceProvider
{
    public function registeringPackage(): void
    {
        $this->app['events']->listen(MailEvent::class, LogMailEvent::class);

        $this->app['events']->listen(MessageSending::class, AttachMailLogUuid::class);
        $this->app['events']->listen(MessageSending::class, LogSendingMail::class);
        $this->app['events']->listen(MessageSending::class, StoreMailRelations::class);

        $this->app['events']->listen(MessageSent::class, LogSentMail::class);
        $this->app['events']->listen(MailHardBounced::class, NotifyOnBounce::class);
        $this->app['events']->listen(MailUnsuppressed::class, UnsuppressEmailAddress::class);
    }

    public function bootingPackage(): void
    {
        $this->app->singleton(MailProviderContract::class, fn ($app): MailProviderManager => new MailProviderManager($app));
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-mails')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations($this->getMigrations())
            ->hasRoutes('webhooks')
            ->hasCommands(
                MonitorMailCommand::class,
                PruneMailCommand::class,
                ResendMailCommand::class,
                WebhooksMailCommand::class,
                CheckBounceRateCommand::class,
            );
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return collect(app(Filesystem::class)->files(__DIR__.'/../database/migrations'))
            ->map(fn (SplFileInfo $file): string => str_replace('.php.stub', '', $file->getBasename()))
            ->toArray();
    }
}
