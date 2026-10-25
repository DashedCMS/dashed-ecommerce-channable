<?php

namespace Dashed\DashedEcommerceChannable\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Dashed\DashedEcommerceCore\Models\Product;
use Dashed\DashedEcommerceChannable\Classes\FeedProgress;

class CreateJSONFeedsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 6000;
    public int $tries = 10;

    public function __construct(public bool $trackProgress = false)
    {
    }

    public function handle(): void
    {
        $disk = Storage::disk('dashed');
        $productIds = Product::query()->pluck('id');
        $locales = collect(Locales::getLocales())->map(fn ($locale) => $locale['id'] ?? $locale);

        if ($this->trackProgress) {
            FeedProgress::running(DB::table('dashed__product_feed_data')
                ->whereIn('locale', $locales)
                ->whereIn('product_id', $productIds)
                ->count());
        }

        foreach (Locales::getLocales() as $locale) {
            $localeId = $locale['id'] ?? $locale;
            $path = "channable-feeds/channable-feed-{$localeId}.json";

            $disk->put($path, '[');

            $first = true;

            DB::table('dashed__product_feed_data')
                ->where('locale', $localeId)
                ->whereIn('product_id', $productIds)
                ->orderBy('product_id')
                ->chunkById(1000, function ($rows) use ($disk, $path, &$first) {
                    $buffer = '';

                    foreach ($rows as $row) {
                        if (empty($row->payload)) {
                            continue;
                        }

                        if (! $first) {
                            $buffer .= ',';
                        } else {
                            $first = false;
                        }

                        $buffer .= $row->payload;
                    }

                    if ($buffer !== '') {
                        $disk->append($path, $buffer, '');
                    }

                    if ($this->trackProgress) {
                        FeedProgress::advance($rows->count());
                    }
                }, 'product_id');

            $disk->append($path, ']', '');
        }

        if ($this->trackProgress) {
            FeedProgress::done();
        }
    }

    public function failed(\Throwable $exception): void
    {
        logger()->error('CreateJSONFeedsJob failed', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        if ($this->trackProgress) {
            FeedProgress::failed($exception->getMessage());
        }
    }
}
