<?php

namespace Backstage\Mails\Laravel\Tests\Fixtures;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TestMailable extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            from: 'from@example.com',
            subject: 'Test Mailable',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Test HTML content</p>',
        );
    }
}
