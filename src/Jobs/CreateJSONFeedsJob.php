<?php

namespace Dashed\DashedEcommerceChannable\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedEcommerceCore\Models\Product;
use Dashed\DashedEcommerceChannable\Resources\ChannableProductResource;

class CreateJSONFeedsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 6000;
    public int $tries = 10;

    public function __construct()
    {
    }

    public function handle(): void
    {
        foreach (Locales::getLocales() as $locale) {
            $localeId = $locale['id'] ?? $locale;

            App::setLocale($localeId);

            // Start van de JSON array
            $json = '[';
            $isFirstItem = true;

            $query = Product::publicShowable()
                ->with([
                    'productCategories',
                    'productGroup',
                    'productGroup.activeProductFilters.productFilterOptions',
                    'productFilters', // met pivot
                    'productCharacteristics.productCharacteristic',
                    'productGroup.productCharacteristics.productCharacteristic',
                ]);

            $query->chunkById(500, function ($products) use (&$json, &$isFirstItem) {
                foreach ($products as $product) {
                    $resource = new ChannableProductResource($product);
                    $itemArray = $resource->toArray(null);

                    $itemJson = json_encode($itemArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    if (! $isFirstItem) {
                        $json .= ',';
                    }

                    $json .= $itemJson;
                    $isFirstItem = false;
                }
            });

            $json .= ']';

            Storage::disk('dashed')->put(
                "channable-feeds/channable-feed-{$localeId}.json",
                $json
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        logger()->error('CreateJSONFeedsJob failed', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
