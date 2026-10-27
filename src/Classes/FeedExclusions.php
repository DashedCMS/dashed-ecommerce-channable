<?php

namespace Dashed\DashedEcommerceChannable\Classes;

use Closure;

/**
 * Product groups a site keeps out of everything that goes to Channable (feed and stock sync),
 * for example hidden made-to-order products that exist for a single customer.
 */
class FeedExclusions
{
    private static ?Closure $resolver = null;

    /**
     * @param  (Closure(): iterable<int>)|null  $resolver  Returns the product group ids to leave out.
     */
    public static function excludeProductGroupsUsing(?Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    /** @return list<int> */
    public static function productGroupIds(): array
    {
        if (! self::$resolver) {
            return [];
        }

        return collect((self::$resolver)())
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Leaves the excluded groups out of a query on the products table; products without a group stay.
     */
    public static function apply($query, string $column = 'product_group_id')
    {
        $groupIds = self::productGroupIds();

        if ($groupIds === []) {
            return $query;
        }

        return $query->where(fn ($query) => $query->whereNull($column)->orWhereNotIn($column, $groupIds));
    }
}
