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
            'image_link' => url('/storage/' . $this->firstImageUrl),
//            'image_link' => app(\Dashed\Drift\UrlBuilder::class)->url('dashed', $this->firstImageUrl, []),
        ];

        $imageCount = 1;
        $additionalImageLinks = '';
        foreach ($this->allImagesExceptFirst as $image) {
            if ($imageCount == 1) {
                $additionalImageLinks .= url('/storage/' . $image['image']);
                $additionalImageLinks .= mediaHelper()->getSingleImage($image['image'], 'medium')->url ?? '';
            } else {
                $additionalImageLinks .= ';' . url('/storage/' . $image['image']);
                $additionalImageLinks .= ';' . mediaHelper()->getSingleImage($image['image'], 'medium')->url ?? '';
            }
            $imageCount++;
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
