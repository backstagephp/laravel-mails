<?php

use Aws\History;
use Aws\Middleware;
use Aws\MockHandler;
use Aws\Result;
use Aws\Ses\SesClient;
use Aws\SesV2\SesV2Client;
use Aws\Sns\SnsClient;
use Backstage\Mails\Laravel\Drivers\SesDriver;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

// The AWS SDK validates every call against the real service definitions before
// it would hit the network, so running the driver against mocked handlers
// proves the calls are shaped like the SES/SNS APIs expect. The SDK is only a
// suggested dependency, so these tests skip when it is not installed.

$awsSdkMissing = fn (): bool => ! class_exists(SesClient::class);

function mockedSesDriver(MockHandler $sesHandler, MockHandler $snsHandler, MockHandler $sesV2Handler, History $history): SesDriver
{
    return new class($sesHandler, $snsHandler, $sesV2Handler, $history) extends SesDriver
    {
        public function __construct(
            private MockHandler $sesHandler,
            private MockHandler $snsHandler,
            private MockHandler $sesV2Handler,
            private History $history,
        ) {
            parent::__construct();
        }

        protected function createSesClient(array $config): SesClient
        {
            return $this->record(new SesClient([
                'version' => 'latest',
                'region' => 'us-east-1',
                'credentials' => false,
                'handler' => $this->sesHandler,
            ]));
        }

        protected function createSnsClient(array $config): SnsClient
        {
            return $this->record(new SnsClient([
                'version' => 'latest',
                'region' => 'us-east-1',
                'credentials' => false,
                'handler' => $this->snsHandler,
            ]));
        }

        protected function createSesV2Client(array $config): SesV2Client
        {
            return $this->record(new SesV2Client([
                'version' => 'latest',
                'region' => 'us-east-1',
                'credentials' => false,
                'handler' => $this->sesV2Handler,
            ]));
        }

        private function record($client)
        {
            $client->getHandlerList()->appendSign(Middleware::history($this->history));

            return $client;
        }
    };
}

function consoleComponents(BufferedOutput $output): Factory
{
    return new Factory(new OutputStyle(new ArrayInput([]), $output));
}

it('registers webhooks with calls the ses and sns apis accept', function (): void {
    config()->set('services.ses', [
        'key' => 'key',
        'secret' => 'secret',
        'region' => 'us-east-1',
        'account_id' => '123456789012',
    ]);

    $sesHandler = new MockHandler;
    $sesHandler->append(new Result([])); // createConfigurationSet
    $sesHandler->append(new Result([])); // deleteConfigurationSetEventDestination
    $sesHandler->append(new Result([])); // createConfigurationSetEventDestination

    $snsHandler = new MockHandler;
    $snsHandler->append(new Result(['TopicArn' => 'arn:aws:sns:us-east-1:123456789012:laravel-mails-ses-webhook']));
    $snsHandler->append(new Result([])); // addPermission
    $snsHandler->append(new Result(['SubscriptionArn' => 'pending confirmation'])); // subscribe

    $history = new History;
    $driver = mockedSesDriver($sesHandler, $snsHandler, new MockHandler, $history);

    $driver->registerWebhooks(consoleComponents($output = new BufferedOutput));

    expect($output->fetch())->toContain('Created SES Webhooks for');

    $commands = collect($history)->map(fn ($entry) => $entry['command']->getName())->values()->all();

    expect($commands)->toBe([
        'CreateConfigurationSet',
        'CreateTopic',
        'AddPermission',
        'DeleteConfigurationSetEventDestination',
        'CreateConfigurationSetEventDestination',
        'Subscribe',
    ]);

    $eventDestination = collect($history)->first(
        fn ($entry): bool => $entry['command']->getName() === 'CreateConfigurationSetEventDestination'
    )['command'];

    expect($eventDestination['ConfigurationSetName'])->toBe('laravel-mails-ses-webhook');
    expect($eventDestination['EventDestination']['MatchingEventTypes'])
        ->toBe(['open', 'click', 'delivery', 'reject', 'bounce', 'complaint']);
    expect($eventDestination['EventDestination']['SNSDestination']['TopicARN'])
        ->toBe('arn:aws:sns:us-east-1:123456789012:laravel-mails-ses-webhook');

    $subscribe = collect($history)->first(
        fn ($entry): bool => $entry['command']->getName() === 'Subscribe'
    )['command'];

    expect($subscribe['Protocol'])->toBe('https');
    expect($subscribe['Endpoint'])->toContain('/webhooks/mails/ses?signature=');
})->skip($awsSdkMissing, 'aws/aws-sdk-php is not installed');

it('fails gracefully when the ses configuration is incomplete', function (): void {
    config()->set('services.ses', []);

    $driver = new SesDriver;

    // No region configured, so the client cannot even be constructed. That
    // should surface as console output, not as an exception.
    $driver->registerWebhooks(consoleComponents($output = new BufferedOutput));

    expect($output->fetch())->toContain('Failed to create SES webhook');
})->skip($awsSdkMissing, 'aws/aws-sdk-php is not installed');

it('unsuppresses an email address through the sesv2 api', function (): void {
    $sesV2Handler = new MockHandler;
    $sesV2Handler->append(new Result([])); // deleteSuppressedDestination

    $history = new History;
    $driver = mockedSesDriver(new MockHandler, new MockHandler, $sesV2Handler, $history);

    $response = $driver->unsuppressEmailAddress('suppressed@example.com');

    expect($response->successful())->toBeTrue();

    $command = collect($history)->first()['command'];

    expect($command->getName())->toBe('DeleteSuppressedDestination');
    expect($command['EmailAddress'])->toBe('suppressed@example.com');
})->skip($awsSdkMissing, 'aws/aws-sdk-php is not installed');

it('returns a client error response when unsuppressing fails', function (): void {
    $sesV2Handler = new MockHandler;
    $sesV2Handler->append(function ($command) {
        return new Aws\Exception\AwsException('Access denied', $command);
    });

    $driver = mockedSesDriver(new MockHandler, new MockHandler, $sesV2Handler, new History);

    $response = $driver->unsuppressEmailAddress('suppressed@example.com');

    expect($response->failed())->toBeTrue();
})->skip($awsSdkMissing, 'aws/aws-sdk-php is not installed');
