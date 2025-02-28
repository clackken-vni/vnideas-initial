<?php

namespace Vnideas\Initial\Commands;

use Illuminate\Console\Command;

class InitialCommand extends Command
{
    public $signature = 'initial';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
