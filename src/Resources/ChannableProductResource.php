<?php

namespace Dashed\DashedEcommerceChannable\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChannableProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $categories = [];

        foreach ($this->productCategories as $category) {
            $categories[] = $category->name;
        }

        $array = [
            'id' => $this->id,
            'title' => $this->name,
            'link' => $this->getUrl(),
            'price' => $this->currentPrice,
            'sale_price' => $this->discountPrice,
            'availability' => $this->directSellableStock() ? true : false,
            'stock' => $this->directSellableStock(),
            'description' => $this->description,
            'ean' => $this->ean,
            'sku' => $this->sku,
            'image_link' => $this->firstImage ? (mediaHelper()->getSingleMedia($this->firstImage, 'original')->url ?? '') : null,
            'first_category' => $this->productCategories->first() ? $this->productCategories->first()->name : null,
            'categories' => $categories,
        ];

        $imageCount = 1;
        $additionalImageLinks = '';
        foreach ($this->imagesExceptFirst as $image) {
            if ($imageCount == 1) {
                if ($image) {
                    $additionalImageLinks .= mediaHelper()->getSingleMedia($image, 'original')->url ?? '';
                    $imageCount++;
                }
            } else {
                if ($image) {
                    $additionalImageLinks .= ';' . mediaHelper()->getSingleMedia($image, 'original')->url ?? '';
                    $imageCount++;
                }
            }
        }
        $array['additional_image_link'] = $additionalImageLinks;

        foreach ($this->productCharacteristics as $productCharacteristic) {
            if ($productCharacteristic->value !== null && $productCharacteristic->value !== '') {
                $array[$productCharacteristic->productCharacteristic->name] = $productCharacteristic->value;
            }
        }

        return $array;
    }
}
