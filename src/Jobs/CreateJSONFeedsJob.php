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

            $path = "channable-feeds/channable-feed-{$localeId}.json";

            // open stream naar storage (lokaal disk = ok)
            $disk = Storage::disk('dashed');

            // Maak leeg bestand + begin bracket
            $disk->put($path, '[');

            $first = true;

            Product::publicShowable()
                ->with([
                    'productCategories',
                    'productGroup',
                    'productGroup.activeProductFilters.productFilterOptions',
                    'productFilters',
                    'productCharacteristics.productCharacteristic',
                    'productGroup.productCharacteristics.productCharacteristic',
                ])
                ->orderBy('id')
                ->chunkById(500, function ($products) use ($disk, $path, &$first) {
                    $buffer = '';

                    foreach ($products as $product) {
                        $item = (new ChannableProductResource($product))->toArray(null);

                        $json = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        // comma handling
                        if (! $first) {
                            $buffer .= ',';
                        } else {
                            $first = false;
                        }

                        $buffer .= $json;
                    }

                    // append chunk
                    $disk->append($path, $buffer);
                });

            // sluit array af
            $disk->append($path, ']');
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
