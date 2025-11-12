<?php

namespace Dashed\DashedEcommerceChannable\Resources;

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
        $categories = $this->productCategories
            ? $this->productCategories->pluck('name')->values()->all()
            : [];

        $filters = $this->productGroup?->simpleFilters() ?? [];
        foreach ($filters as &$filter) {
            $productFilterResult = $this->productFilters()->where('product_filter_id', $filter['id'])->first();
            if ($productFilterResult) {
                $filter['active'] = $productFilterResult->pivot->product_filter_option_id ?? null;
            } elseif (count($filter['options'] ?? []) === 1) {
                $filter['active'] = $filter['options'][0]['id'];
            }
        }
        unset($filter); // break reference

        // 3) Images (één keer bepalen, geen unset-truc)
        $images = $this->originalImagesToShow;
        if (empty($images) && $this->productGroup) {
            $images = $this->productGroup->originalImagesToShow;
        }
        $imageLink = $images[0] ?? null;

        // 4) Stock/availability (snelle velden, met fallback)
        $stock = $this->total_stock ?? null;
        $availability = $this->in_stock ?? null;
        if ($stock === null || $availability === null) {
            $stock = $this->directSellableStock();
            $availability = $stock > 0;
        }

        // 5) Descriptions met bestaande replaceContentVariables signature
        $description = null;
        $shortDescription = null;

        if (isset($this->product) && $this->product && $this->product->description) {
            $description = $this->product->replaceContentVariables($this->product->description, $filters);
        } elseif ($this->productGroup) {
            $description = $this->productGroup->replaceContentVariables($this->productGroup->description, $filters, $this->product);
        }

        if (isset($this->product) && $this->product && $this->product->short_description) {
            $shortDescription = $this->product->replaceContentVariables($this->product->short_description, $filters);
        } elseif ($this->productGroup) {
            $shortDescription = $this->productGroup->replaceContentVariables($this->productGroup->short_description, $filters, $this->product);
        }

        $characteristicsMap = [];

        if ($this->productGroup && $this->productGroup->relationLoaded('activeProductFilters')) {
            foreach ($this->productGroup->activeProductFilters as $filterModel) {
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


        if ($this->productGroup) {
            foreach ($this->productGroup->allCharacteristicsWithoutFilters() as $gc) {
                if ($gc['value']) {
                    $characteristicsMap[$gc['name']] = $gc['value'];
                }
            }
        }

        foreach ($this->allCharacteristics() as $gc) {
            if ($gc['value']) {
                $characteristicsMap[$gc['name']] = $gc['value'];
            }
            //        }
            //        foreach ($this->allCharacteristics() as $gc) {
            //            if($gc['value']){
            //                $characteristicsMap[$gc['name']] = $gc['value'];
            //            }
        }

        // 7) Basis payload
        $array = [
            'id' => $this->id,
            'product_group_id' => $this->product_group_id ?? ($this->productGroup->id ?? null),
            'title' => $this->name,
            'link' => url($this->getUrl()),
            'price' => $this->currentPrice,
            'sale_price' => $this->discountPrice,
            'availability' => (bool)$availability,
            'stock' => $stock,
            'description' => $description,
            'short_description' => $shortDescription,
            'ean' => $this->ean,
            'sku' => $this->sku,
            'image_link' => $imageLink,
            'first_category' => $categories[0] ?? null,
            'categories' => $categories,
            'width' => $this->width,
            'height' => $this->height,
            'length' => $this->length,
            'weight' => $this->weight,
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
