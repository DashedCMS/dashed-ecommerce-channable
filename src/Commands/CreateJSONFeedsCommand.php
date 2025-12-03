<?php

namespace Dashed\DashedEcommerceChannable\Commands;

use Illuminate\Console\Command;
use Dashed\DashedEcommerceChannable\Jobs\CreateJSONFeedsJob;

class CreateJSONFeedsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channable:create-json-feeds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create JSON feeds for Channable';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        CreateJSONFeedsJob::dispatchSync();
    }
}
