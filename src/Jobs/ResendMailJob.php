<?php

namespace Backstage\Mails\Jobs;

use Backstage\Mails\Models\Mail as Mailable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ResendMailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use InteractsWithSockets;
    use Queueable;
    use SerializesModels;

    /**
     * @param  non-empty-array<int, string>  $to
     */
    public function __construct(
        private readonly Mailable $mailable,
        private array $to,
        private array $cc = [],
        private array $bcc = [],
        private array $replyTo = []
    ) {
        //
    }

    public function handle(): void
    {
        Mail::send([], [], function (Message $message): void {
            $this->setMessageContent($message)
                ->setMessageRecipients($message);
        });
    }

    private function setMessageContent(Message $message): self
    {
        $message->html($this->mailable->html ?? '')
            ->text($this->mailable->text ?? '');

        foreach ($this->mailable->attachments as $attachment) {
            $message->attachData(
                $attachment->file_data ?? $attachment->fileData ?? '',
                $attachment->file_name ?? $attachment->filename ?? '',
                ['mime' => $attachment->mime_type ?? $attachment->mime ?? '']
            );
        }

        return $this;
    }

    private function setMessageRecipients(Message $message): self
    {
        $message->subject($this->mailable->subject ?? '')
            ->from(array_keys($this->mailable->from)[0], array_values($this->mailable->from)[0])
            ->to($this->to);

        if ($this->mailable->cc || count($this->cc) > 0) {
            $message->cc($this->mailable->cc ?? $this->cc);
        }

        if ($this->mailable->bcc || count($this->bcc) > 0) {
            $message->bcc($this->mailable->bcc ?? $this->bcc);
        }

        if ($this->mailable->reply_to || $this->replyTo) {
            $message->replyTo($this->mailable->reply_to ?? $this->replyTo);
        }

        return $this;
    }
}
