<?php

namespace Dashed\DashedEcommerceChannable\Resources;

use Dashed\DashedEcommerceCore\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

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
    public function toArray($request)
    {
        $product = Product::find($this->id);

        $categories = $product->productCategories
            ? $product->productCategories->pluck('name')->values()->all()
            : [];

        $filters = $product->productGroup?->simpleFilters() ?? [];
        foreach ($filters as &$filter) {
            $productFilterResult = $product->productFilters()->where('product_filter_id', $filter['id'])->first();
            if ($productFilterResult) {
                $filter['active'] = $productFilterResult->pivot->product_filter_option_id ?? null;
            } elseif (count($filter['options'] ?? []) === 1) {
                $filter['active'] = $filter['options'][0]['id'];
            }
        }
        unset($filter); // break reference

        // 3) Images (één keer bepalen, geen unset-truc)
        $images = $product->originalImagesToShow;
        if (empty($images) && $product->productGroup) {
            $images = $product->productGroup->originalImagesToShow;
        }
        $imageLink = $images[0] ?? null;

        // 4) Stock/availability (snelle velden, met fallback)
        $stock = $product->total_stock ?? null;
        $availability = $product->in_stock ?? null;
        if ($stock === null || $availability === null) {
            $stock = $product->directSellableStock();
            $availability = $stock > 0;
        }

        // 5) Descriptions met bestaande replaceContentVariables signature
        $description = null;
        $shortDescription = null;

        if (isset($product) && $product && $product->description) {
            $description = $product->replaceContentVariables($product->description, $filters);
        } elseif ($product->productGroup) {
            $description = $product->productGroup->replaceContentVariables($product->productGroup->description, $filters, $product);
        }

        if (isset($product) && $product && $product->short_description) {
            $shortDescription = $product->replaceContentVariables($product->short_description, $filters);
        } elseif ($product->productGroup) {
            $shortDescription = $product->productGroup->replaceContentVariables($product->productGroup->short_description, $filters, $product);
        }

        $characteristicsMap = [];

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


        if ($product->productGroup) {
            foreach ($product->productGroup->allCharacteristicsWithoutFilters() as $gc) {
                if ($gc['value']) {
                    $characteristicsMap[$gc['name']] = $gc['value'];
                }
            }
        }

        foreach ($product->allCharacteristics() as $gc) {
            if ($gc['value']) {
                $characteristicsMap[$gc['name']] = $gc['value'];
            }
            //        }
            //        foreach ($product->allCharacteristics() as $gc) {
            //            if($gc['value']){
            //                $characteristicsMap[$gc['name']] = $gc['value'];
            //            }
        }

        // 7) Basis payload
        $array = [
            'id' => $product->id,
            'product_group_id' => $product->product_group_id ?? ($product->productGroup->id ?? null),
            'title' => $product->name,
            'link' => url($product->getUrl()),
            'price' => $product->currentPrice,
            'sale_price' => $product->discountPrice,
            'availability' => (bool)$availability,
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
