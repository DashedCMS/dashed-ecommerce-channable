<?php

namespace Dashed\DashedEcommerceChannable\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\App;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
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

            $path = "channable-feeds/channable-feed-{$localeId}.json";
            $disk = Storage::disk('dashed');

            $disk->put($path, '[');
            $first = true;

            DB::table('dashed__product_feed_data')
                ->where('locale', $localeId)
                ->orderBy('product_id')
                ->chunkById(1000, function ($rows) use ($disk, $path, &$first) {
                    $buffer = '';

                    foreach ($rows as $row) {
                        if (! $first) {
                            $buffer .= ',';
                        } else {
                            $first = false;
                        }

                        $buffer .= $row->payload;
                    }

                    $disk->append($path, $buffer);
                }, 'product_id');

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
