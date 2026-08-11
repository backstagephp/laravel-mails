<?php

use Backstage\Mails\Laravel\Contracts\HasAssociatedMails;
use Backstage\Mails\Laravel\Models\Mail as MailModel;
use Backstage\Mails\Laravel\Traits\AssociatesModels;
use Backstage\Mails\Laravel\Traits\HasMails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Mailer\Transport\NullTransport;

class TestOrder extends Model implements HasAssociatedMails
{
    use HasMails;

    protected $table = 'test_orders';

    protected $guarded = [];
}

class TestOrderShipped extends Mailable
{
    use AssociatesModels;

    public function __construct(TestOrder $order)
    {
        $this->associateWith($order);
    }

    public function build(): self
    {
        return $this->subject('Your order has shipped')->html('<p>On its way</p>');
    }
}

beforeEach(function (): void {
    // Association rides along on the tracking uuid, which is only attached for
    // mailers whose transport has a driver. Stand in for a real provider
    // transport, which is not installed here.
    Mail::extend('resend', fn (): NullTransport => new NullTransport);

    config()->set('mail.mailers.resend.transport', 'resend');
    config()->set('mail.default', 'resend');

    Schema::create('test_orders', function ($table): void {
        $table->id();
        $table->timestamps();
    });
});

it('associates a model with the mail that was sent about it', function (): void {
    $order = TestOrder::create();

    Mail::to('customer@example.com')->send(new TestOrderShipped($order));

    $mail = MailModel::latest()->first();

    expect($order->refresh()->mails)->toHaveCount(1);
    expect($order->mails->first()->is($mail))->toBeTrue();
    expect($order->mails->first()->subject)->toBe('Your order has shipped');
});

it('can associate a mail with a model by hand', function (): void {
    $order = TestOrder::create();

    $mail = MailModel::factory()->create();

    $order->associateMail($mail);

    expect($order->refresh()->mails)->toHaveCount(1);
});
