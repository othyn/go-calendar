<?php

declare(strict_types=1);

namespace Console\Services;

use Console\Enums\OutputGroup;
use Symfony\Component\Console\Output\OutputInterface;

class OutputService
{
    protected const LINE_INDENT_CHAR = '─';

    public function __construct(
        protected OutputInterface $output
    ) {
    }

    /**
     * Output a new line to the console in the standard format.
     */
    public function msg(OutputGroup $group, string $message): void
    {
        $msg = $group->prefix();

        $msg .= str_repeat(
            string: self::LINE_INDENT_CHAR,
            times: $group->indent()
        );

        $msg .= "[{$group->value}] ";

        $msg .= $message;

        $this->output->writeln(
            messages: $msg
        );
    }
}
