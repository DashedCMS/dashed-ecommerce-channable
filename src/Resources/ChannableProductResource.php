<?php

namespace Dashed\DashedEcommerceChannable\Resources;

use Dashed\DashedEcommerceCore\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Dashed\DashedEcommerceCore\Models\Product
 */
class ChannableProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Let op:
     * - Eager-load in je query: productCategories, productGroup.activeProductFilters.productFilterOptions,
     *   productFilters (met pivot), productCharacteristics.productCharacteristic,
     *   productGroup.productCharacteristics.productCharacteristic.
     */
    public function toArray($request): array
    {
        /** @var Product $product */
        $product = $this->resource;

        // 1) Categories
        $categories = $product->productCategories
            ? $product->productCategories->pluck('name')->values()->all()
            : [];

        // 2) Filters + actieve optie bepalen
        $filters = $product->productGroup?->simpleFilters() ?? [];
        $productFilters = $product->relationLoaded('productFilters')
            ? $product->productFilters
            : collect();

        foreach ($filters as &$filter) {
            $filterId = $filter['id'] ?? null;

            if (! $filterId) {
                continue;
            }

            $productFilterResult = $productFilters->firstWhere('product_filter_id', $filterId);

            if ($productFilterResult) {
                $filter['active'] = $productFilterResult->pivot->product_filter_option_id ?? null;
            } elseif (count($filter['options'] ?? []) === 1) {
                $filter['active'] = $filter['options'][0]['id'];
            }
        }
        unset($filter);

        // 3) Images
        $images = $product->originalImagesToShow;
        if (empty($images) && $product->productGroup) {
            $images = $product->productGroup->originalImagesToShow ?? [];
        }
        $imageLink = $images[0] ?? null;

        // 4) Stock / availability
        $stock = $product->total_stock;
        $availability = $product->in_stock;

        if ($stock === null || $availability === null) {
            $stock = $product->directSellableStock();
            $availability = $stock > 0;
        }

        // 5) Descriptions
        $description = null;
        $shortDescription = null;

        if ($product->description) {
            $description = $product->replaceContentVariables($product->description, $filters);
        } elseif ($product->productGroup && $product->productGroup->description) {
            $description = $product->productGroup->replaceContentVariables(
                $product->productGroup->description,
                $filters,
                $product
            );
        }

        if ($product->short_description) {
            $shortDescription = $product->replaceContentVariables($product->short_description, $filters);
        } elseif ($product->productGroup && $product->productGroup->short_description) {
            $shortDescription = $product->productGroup->replaceContentVariables(
                $product->productGroup->short_description,
                $filters,
                $product
            );
        }

        // 6) Characteristics map
        $characteristicsMap = [];

        // Filters als kenmerken
        if ($product->productGroup && $product->productGroup->relationLoaded('activeProductFilters')) {
            foreach ($product->productGroup->activeProductFilters as $filterModel) {
                $value = '';
                $activeId = null;

                foreach ($filters as $f) {
                    if (($f['id'] ?? null) === $filterModel->id) {
                        $activeId = $f['active'] ?? null;

                        break;
                    }
                }

                if ($activeId) {
                    $opt = $filterModel->productFilterOptions->firstWhere('id', $activeId);
                    if ($opt) {
                        $value = $opt->name ?? 'onbekend';
                    }
                }

                $characteristicsMap[$filterModel->name] = $value;
            }
        }

        // Group characteristics
        if ($product->productGroup) {
            foreach ($product->productGroup->allCharacteristicsWithoutFilters() as $gc) {
                if (! empty($gc['value'])) {
                    $characteristicsMap[$gc['name']] = $gc['value'];
                }
            }
        }

        // Product characteristics (product > group, product wint)
        foreach ($product->allCharacteristics() as $gc) {
            if (! empty($gc['value'])) {
                $characteristicsMap[$gc['name']] = $gc['value'];
            }
        }

        // 7) Basis payload
        $array = [
            'id' => $product->id,
            'product_group_id' => $product->product_group_id ?? ($product->productGroup->id ?? null),
            'title' => $product->name,
            'link' => url($product->getUrl()),
            'price' => $product->currentPrice,
            'sale_price' => $product->discountPrice,
            'availability' => (bool) $availability,
            'stock' => $stock,
            'description' => $description,
            'short_description' => $shortDescription,
            'ean' => $product->ean,
            'sku' => $product->sku,
            'image_link' => $imageLink,
            'images' => $images,
            'first_category' => $categories[0] ?? null,
            'categories' => $categories,
            'width' => $product->width,
            'height' => $product->height,
            'length' => $product->length,
            'weight' => $product->weight,
        ];

        // 8) Extra images als image_link_2..n
        if (! empty($images)) {
            foreach (array_values(array_slice($images, 1)) as $idx => $url) {
                $array['image_link_' . ($idx + 2)] = $url;
            }
        }

        // 9) Characteristics vlak in array
        foreach ($characteristicsMap as $name => $value) {
            if ($value !== null && $value !== '') {
                $array[$name] = $value;
            }
        }

        return $array;
    }
}
