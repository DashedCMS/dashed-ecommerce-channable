<?php

namespace Dashed\DashedEcommerceChannable\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\App;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Dashed\DashedEcommerceCore\Models\Product;
use Dashed\DashedEcommerceChannable\Resources\ChannableProductResource;

class CreateJSONFeedsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 6000;
    public $tries = 10;

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

    public function failed(\Throwable $exception)
    {
        throw new \Exception('CreateJSONFeedsJob failed: ' . $exception->getMessage());
    }
}
