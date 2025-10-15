<?php

namespace Backstage\Mails\Commands;

use Backstage\Mails\Jobs\ResendMailJob;
use Backstage\Mails\Models\Mail;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class ResendMailCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'mail:resend {uuid} {to?*} {--cc=*} {--bcc=*}';

    public $description = 'Resend mail';

    public function handle(): int
    {
        $uuid = $this->argument('uuid');

        $mail = mail::where('uuid', $uuid)->first();

        if (is_null($mail)) {
            $this->components->error("Mail with uuid: \"{$uuid}\" does not exist");

            return Command::FAILURE;
        }

        info('For the next prompts you can input multiple email addresses by separating them with a comma.');

        [$to, $cc, $bcc] = $this->promptemailinputs($mail);

        ResendMailJob::dispatch($mail, $to, $cc, $bcc);

        info('All done');

        return self::SUCCESS;
    }

    protected function promptEmailInputs(Mail $mail): array
    {
        $to = in_array(implode(',', $this->argument('to')), ['', '0'], true) ? text(
            label: 'What email address do you want to send the mail to?',
            placeholder: 'test@example.com',
        ) : implode(',', $this->argument('to'));

        $cc = in_array(implode(',', $this->option('cc')), ['', '0'], true) ? text(
            label: 'What email address should be included in the cc?',
            placeholder: 'test@example.com',
        ) : implode(',', $this->option('cc'));

        $bcc = in_array(implode(',', $this->option('bcc')), ['', '0'], true) ? text(
            label: 'What email address should be included in the bcc?',
            placeholder: 'test@example.com',
        ) : implode(',', $this->option('bcc'));

        foreach ([&$to, &$cc, &$bcc] as &$input) {
            $input = array_filter(array_map(fn ($s): string => trim($s), explode(' ', str_replace([',', ';'], ' ', $input))));
        }

        return [$to !== '' && $to !== '0' ? $to : $mail->to, $cc !== '' && $cc !== '0' ? $cc : $mail->cc ?? [], $bcc !== '' && $bcc !== '0' ? $bcc : $mail->bcc ?? []];
    }

    protected function promptForMissingArgumentsUsing()
    {
        return ['uuid' => 'What is the UUID of the email you want to re-send?'];
    }
}
