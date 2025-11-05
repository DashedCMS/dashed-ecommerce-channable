<?php

namespace Dashed\DashedEcommerceChannable\Commands;

use Dashed\DashedEcommerceChannable\Jobs\CreateJSONFeedsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Support\Facades\Storage;
use Dashed\DashedEcommerceCore\Models\Product;
use Dashed\DashedEcommerceChannable\Resources\ChannableProductResource;

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
        CreateJSONFeedsJob::dispatch()->onQueue('ecommerce');
    }
}
