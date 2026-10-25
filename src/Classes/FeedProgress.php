<?php

namespace Dashed\DashedEcommerceChannable\Classes;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Progress of a feed refresh started from the settings page, shared between
 * the web request and the queue worker through the cache.
 */
class FeedProgress
{
    public const CACHE_KEY = 'dashed-channable-feed-progress';

    public const STALE_AFTER_MINUTES = 30;

    public static function get(): ?array
    {
        return Cache::get(self::CACHE_KEY);
    }

    public static function startWithProducts(int $groupsTotal): void
    {
        self::put([
            'status' => $groupsTotal > 0 ? 'products' : 'queued',
            'groups_total' => $groupsTotal,
            'groups_done' => 0,
            'rows_total' => 0,
            'rows_done' => 0,
            'started_at' => now()->toIso8601String(),
        ]);
    }

    public static function queue(): void
    {
        self::startWithProducts(0);
    }

    /**
     * Counts a finished product group: 'last' when the feed can be written,
     * 'counted' while groups are still pending, 'none' without a run.
     */
    public static function groupDone(): string
    {
        return Cache::lock(self::CACHE_KEY . '-lock', 10)->block(5, function () {
            $progress = self::get();

            if (($progress['status'] ?? null) !== 'products' || self::isStale($progress)) {
                return 'none';
            }

            $progress['groups_done']++;
            $last = $progress['groups_done'] >= $progress['groups_total'];

            if ($last) {
                $progress['status'] = 'queued';
            }

            self::put($progress);

            return $last ? 'last' : 'counted';
        });
    }

    /**
     * Lets a feed job take over a queued run from the settings page. Only one
     * job wins, so a parallel rebuild never reports into the same progress.
     */
    public static function claim(): bool
    {
        return Cache::lock(self::CACHE_KEY . '-lock', 10)->block(5, function () {
            $progress = self::get();

            if (($progress['status'] ?? null) !== 'queued' || self::isStale($progress)) {
                return false;
            }

            $progress['status'] = 'running';
            self::put($progress);

            return true;
        });
    }

    public static function running(int $rowsTotal): void
    {
        self::update([
            'status' => 'running',
            'rows_total' => $rowsTotal,
            'rows_done' => 0,
        ]);
    }

    public static function advance(int $rows): void
    {
        $progress = self::get();

        if (($progress['status'] ?? null) !== 'running') {
            return;
        }

        $progress['rows_done'] = min($progress['rows_total'], $progress['rows_done'] + $rows);
        self::put($progress);
    }

    public static function done(): void
    {
        Cache::forget('dashed-channable-feed-files');

        self::update([
            'status' => 'done',
            'finished_at' => now()->toIso8601String(),
        ]);
    }

    public static function failed(string $message): void
    {
        self::update([
            'status' => 'failed',
            'error' => $message,
            'finished_at' => now()->toIso8601String(),
        ]);
    }

    public static function failedIfRunning(string $message): void
    {
        if ((self::get()['status'] ?? null) === 'running') {
            self::failed($message);
        }
    }

    public static function isActive(?array $progress = null): bool
    {
        $progress ??= self::get();

        return in_array($progress['status'] ?? null, ['products', 'queued', 'running'], true)
            && ! self::isStale($progress);
    }

    public static function isStale(array $progress): bool
    {
        if (! in_array($progress['status'] ?? null, ['products', 'queued', 'running'], true)) {
            return false;
        }

        return Carbon::parse($progress['updated_at'])->lt(now()->subMinutes(self::STALE_AFTER_MINUTES));
    }

    public static function percentage(array $progress): int
    {
        return match ($progress['status'] ?? null) {
            'products' => (int) floor(90 * $progress['groups_done'] / max(1, $progress['groups_total'])),
            'queued' => $progress['groups_total'] > 0 ? 90 : 0,
            'running' => ($progress['groups_total'] > 0 ? 90 : 0)
                + (int) floor(($progress['groups_total'] > 0 ? 10 : 100) * $progress['rows_done'] / max(1, $progress['rows_total'])),
            'done' => 100,
            default => 0,
        };
    }

    private static function update(array $values): void
    {
        self::put(array_merge(self::get() ?? [
            'groups_total' => 0,
            'groups_done' => 0,
            'rows_total' => 0,
            'rows_done' => 0,
            'started_at' => now()->toIso8601String(),
        ], $values));
    }

    private static function put(array $progress): void
    {
        $progress['updated_at'] = now()->toIso8601String();

        Cache::put(self::CACHE_KEY, $progress, now()->addDay());
    }
}
