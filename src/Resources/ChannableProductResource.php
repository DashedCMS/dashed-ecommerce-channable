<?php

namespace Dashed\DashedEcommerceChannable\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChannableProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Performance notes:
     * - Verwacht dat de volgende relaties eager-loaded zijn:
     *   productCategories, productGroup.activeProductFilters.productFilterOptions,
     *   productFilters (met pivot), productCharacteristics.productCharacteristic,
     *   productGroup.productCharacteristics.productCharacteristic
     */
    public function toArray($request)
    {
        // 1) Categories (geen loops met queries)
        $categories = $this->productCategories
            ? $this->productCategories->pluck('name')->values()->all()
            : [];

        // 2) Filters samenstellen ZONDER queries in de loop
        //    - assignedOptions: [product_filter_id => product_filter_option_id]
        $assignedOptions = $this->productFilters
            ? $this->productFilters->mapWithKeys(fn ($pf) => [$pf->id => $pf->pivot->product_filter_option_id])->all()
            : [];

        $filters = [];
        if ($this->productGroup && $this->productGroup->relationLoaded('activeProductFilters')) {
            foreach ($this->productGroup->activeProductFilters as $filter) {
                // Opties lokaal vertalen met fallback
                $options = [];
                foreach ($filter->productFilterOptions as $opt) {
                    $name = $opt->name[app()->getLocale()] ?? ($opt->name[array_key_first((array) $opt->name)] ?? 'onbekend');
                    $options[] = [
                        'id'   => $opt->id,
                        'name' => $name,
                    ];
                }

                $active = $assignedOptions[$filter->id] ?? (count($options) === 1 ? $options[0]['id'] : null);

                $filters[] = [
                    'id'            => $filter->id,
                    'name'          => $filter->name,
                    'type'          => $filter->type,
                    'options'       => $options,
                    'active'        => $active,
                    'contentBlocks' => $filter->contentBlocks, // attribuut, geen query
                ];
            }
        }

        // 3) Afbeeldingen (niet 2x loopen, geen unset-truc)
        //    Pak product-images; zo niet, val terug op productGroup
        $images = $this->originalImagesToShow;
        if (empty($images) && $this->productGroup) {
            $images = $this->productGroup->originalImagesToShow;
        }

        $imageLink = $images[0] ?? null;

        // 4) Stock / availability: snelle velden i.p.v. zware helpers
        //    (Val desnoods terug op directSellableStock() als er geen velden zijn.)
        $stock = $this->total_stock ?? null;
        $availability = $this->in_stock ?? null;

        if ($stock === null || $availability === null) {
            // fallback, maar probeer dit te vermijden; het kan traag zijn
            $stock = $this->directSellableStock();
            $availability = $stock > 0;
        }

        // 5) Descriptions met 1x filters-structuur
        //    Houd bestaande API van replaceContentVariables in stand.
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

        // 6) Characteristics: O(1) mergen via map i.p.v. collect()->where()
        //    a) Filter-waarden (zoals allCharacteristics() dat doet)
        $characteristicsMap = [];

        if ($this->productGroup && $this->productGroup->relationLoaded('activeProductFilters')) {
            foreach ($this->productGroup->activeProductFilters as $filter) {
                // waarde = naam van de geselecteerde optie (op basis van $assignedOptions)
                $value = '';
                $optionId = $assignedOptions[$filter->id] ?? null;
                if ($optionId) {
                    $opt = $filter->productFilterOptions->firstWhere('id', $optionId);
                    if ($opt) {
                        $value = $opt->name[app()->getLocale()] ?? ($opt->name[array_key_first((array) $opt->name)] ?? 'onbekend');
                    }
                }
                $characteristicsMap[$filter->name] = $value;
            }
        }

        //    b) Product characteristics
        if ($this->relationLoaded('productCharacteristics')) {
            foreach ($this->productCharacteristics as $pc) {
                $def = $pc->relationLoaded('productCharacteristic') ? $pc->productCharacteristic : null;
                $name = $def->name ?? null;
                if ($name && $pc->value !== null && $pc->value !== '') {
                    $characteristicsMap[$name] = $pc->value;
                }
            }
        }

        //    c) Group characteristics (overschrijven product waar nodig)
        if ($this->productGroup && $this->productGroup->relationLoaded('productCharacteristics')) {
            foreach ($this->productGroup->productCharacteristics as $gc) {
                $def = $gc->relationLoaded('productCharacteristic') ? $gc->productCharacteristic : null;
                $name = $def->name ?? null;
                if ($name && $gc->value !== null && $gc->value !== '') {
                    $characteristicsMap[$name] = $gc->value;
                }
            }
        }

        // 7) Basis array
        $array = [
            'id'               => $this->id,
            'product_group_id' => $this->product_group_id ?? ($this->productGroup->id ?? null),
            'title'            => $this->name,
            'link'             => url($this->getUrl()),
            'price'            => $this->currentPrice,
            'sale_price'       => $this->discountPrice,
            'availability'     => (bool) $availability,
            'stock'            => $stock,
            'description'      => $description,
            'short_description'=> $shortDescription,
            'ean'              => $this->ean,
            'sku'              => $this->sku,
            'image_link'       => $imageLink,
            'first_category'   => $categories[0] ?? null,
            'categories'       => $categories,
            'width'            => $this->width,
            'height'           => $this->height,
            'length'           => $this->length,
            'weight'           => $this->weight,
        ];

        // 8) Extra images als image_link_2..n (geen tussenstap met ['images'] + unset)
        if (!empty($images)) {
            foreach (array_values(array_slice($images, 1)) as $idx => $url) {
                $array['image_link_' . ($idx + 2)] = $url;
            }
        }

        // 9) Characteristics vlak in array dumpen
        foreach ($characteristicsMap as $name => $value) {
            if ($value !== null && $value !== '') {
                $array[$name] = $value;
            }
        }

        return $array;
    }
}
