<?php

namespace Backstage\Mails\Shared;

use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class Terminal extends Command
{
    protected $name = 'NONEXISTENT';

    protected $hidden = true;

    public ConsoleOutput $outputSymfony;

    public OutputStyle $outputStyle;

    public function __construct(string $argInput = '')
    {
        parent::__construct();

        $this->input = new StringInput($argInput);

        $this->outputSymfony = new ConsoleOutput;
        $this->outputStyle = new OutputStyle($this->input, $this->outputSymfony);

        $this->output = $this->outputStyle;
    }
}
