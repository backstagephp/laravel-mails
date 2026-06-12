<?php

namespace Backstage\Mails\Laravel\Tests\Fixtures;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TaggedMailable extends Mailable
{
    /**
     * @param  array<int, string>  $envelopeTags
     */
    public function __construct(public array $envelopeTags) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tagged subject',
            tags: $this->envelopeTags,
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>Tagged HTML</p>');
    }
}
