<?php

namespace Qubiqx\QcommerceEcommerceChannable\Commands;

use Illuminate\Console\Command;

class QcommerceEcommerceChannableCommand extends Command
{
    public $signature = 'qcommerce-ecommerce-channable';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
