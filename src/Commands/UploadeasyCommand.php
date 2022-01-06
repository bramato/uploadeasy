<?php

namespace Bramato\Uploadeasy\Commands;

use Illuminate\Console\Command;

class UploadeasyCommand extends Command
{
    public $signature = 'uploadeasy';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
