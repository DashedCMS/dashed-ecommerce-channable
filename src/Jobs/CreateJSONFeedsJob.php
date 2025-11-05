<?php

namespace Dashed\DashedCore\Jobs;

use Dashed\DashedEcommerceChannable\Resources\ChannableProductResource;
use Dashed\DashedEcommerceCore\Models\Product;
use Illuminate\Bus\Queueable;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\UrlHistory;
use Illuminate\Queue\InteractsWithQueue;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class CreateJSONFeedsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 2400;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach (Locales::getLocales() as $locale) {
            App::setLocale($locale['id']);

            $json = json_encode(ChannableProductResource::collection(Product::publicShowable()->get()));

            Storage::disk('dashed')->put('/channable-feeds/channable-feed-' . $locale['id'] . '.json', $json);
        }
    }
}
