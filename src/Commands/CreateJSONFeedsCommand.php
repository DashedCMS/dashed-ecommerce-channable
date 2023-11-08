<?php

namespace Dashed\DashedEcommerceChannable\Commands;

use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedEcommerceChannable\Resources\ChannableProductResource;
use Dashed\DashedEcommerceCore\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

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
        foreach (Locales::getLocales() as $locale) {
            App::setLocale($locale['id']);

            $json = json_encode(ChannableProductResource::collection(Product::publicShowable()->get()));

            Storage::disk('dashed')->put('/channable-feeds/channable-feed-' . $locale['id'] . '.json', $json);
        }
    }
}
