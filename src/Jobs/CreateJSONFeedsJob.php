<?php

namespace Dashed\DashedEcommerceChannable\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Dashed\DashedEcommerceChannable\Classes\FeedProgress;

class CreateJSONFeedsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const PENDING_KEY = 'dashed-channable-feed-pending';

    public const FINGERPRINT_KEY = 'dashed-channable-feed-fingerprint';

    public const DEBOUNCE_SECONDS = 60;

    public int $timeout = 6000;
    public int $tries = 10;

    /**
     * @param  bool  $force  Write the feeds even when no feed data changed since the last run.
     */
    public function __construct(public bool $force = false)
    {
    }

    /**
     * Collapses a burst of product updates into one feed rebuild a minute later.
     */
    public static function dispatchDebounced(): void
    {
        if (Cache::add(self::PENDING_KEY, true, self::DEBOUNCE_SECONDS * 5)) {
            static::dispatch()->onQueue('ecommerce')->delay(self::DEBOUNCE_SECONDS);
        }
    }

    public function handle(): void
    {
        // Updates from here on need a new run.
        Cache::forget(self::PENDING_KEY);

        $tracked = FeedProgress::claim();
        $locales = collect(Locales::getLocales())->map(fn ($locale) => $locale['id'] ?? $locale)->values()->all();
        $fingerprint = $this->fingerprint($locales);

        if (! $this->force && ! $tracked && Cache::get(self::FINGERPRINT_KEY) === $fingerprint) {
            return;
        }

        if ($tracked) {
            FeedProgress::running($fingerprint['rows']);
        }

        $disk = Storage::disk('dashed');

        foreach ($locales as $localeId) {
            $this->writeLocale($disk, $localeId, $tracked);
        }

        Cache::forever(self::FINGERPRINT_KEY, $fingerprint);

        if ($tracked) {
            FeedProgress::done();
        }
    }

    /**
     * Builds the feed in a temporary file and uploads it in one go: appending
     * to an S3 object downloads and re-uploads the whole file every time.
     */
    private function writeLocale(Filesystem $disk, string $localeId, bool $tracked): void
    {
        $stream = fopen('php://temp/maxmemory:' . (8 * 1024 * 1024), 'w+b');
        fwrite($stream, '[');
        $first = true;

        $this->feedRows([$localeId])
            ->select('feed.product_id', 'feed.payload')
            ->chunkById(2000, function ($rows) use ($stream, &$first, $tracked) {
                foreach ($rows as $row) {
                    if (empty($row->payload)) {
                        continue;
                    }

                    fwrite($stream, $first ? $row->payload : ',' . $row->payload);
                    $first = false;
                }

                if ($tracked) {
                    FeedProgress::advance($rows->count());
                }
            }, 'feed.product_id', 'product_id');

        fwrite($stream, ']');
        rewind($stream);

        $disk->writeStream("channable-feeds/channable-feed-{$localeId}.json", $stream, [
            'ContentType' => 'application/json',
        ]);

        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    /**
     * Changes whenever a feed row is written, added or removed, or a product is deleted.
     */
    private function fingerprint(array $locales): array
    {
        $stats = $this->feedRows($locales)
            ->selectRaw('count(*) as row_count, max(feed.updated_at) as last_update, sum(feed.product_id) as product_sum')
            ->first();

        return [
            'locales' => $locales,
            'rows' => (int) $stats->row_count,
            'updated_at' => (string) $stats->last_update,
            'products' => (string) $stats->product_sum,
        ];
    }

    private function feedRows(array $locales)
    {
        return DB::table('dashed__product_feed_data as feed')
            ->join('dashed__products as product', 'product.id', '=', 'feed.product_id')
            ->whereNull('product.deleted_at')
            ->whereIn('feed.locale', $locales);
    }

    public function failed(\Throwable $exception): void
    {
        logger()->error('CreateJSONFeedsJob failed', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        FeedProgress::failedIfRunning($exception->getMessage());
    }
}
