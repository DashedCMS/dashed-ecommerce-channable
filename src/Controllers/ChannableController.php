<?php

namespace Dashed\DashedEcommerceChannable\Controllers;

use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Controllers\Frontend\FrontendController;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedEcommerceChannable\Resources\ChannableProductResource;
use Dashed\DashedEcommerceCore\Models\Product;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class ChannableController extends FrontendController
{
    public function index($locale = null)
    {
        if (Customsetting::get('channable_feed_enabled') == 1) {
            $locale = Locales::getLocale($locale);
            App::setLocale($locale['id']);

            return Storage::disk('dashed')->get('/channable-feeds/channable-feed-' . $locale['id'] . '.json');

            return json_encode(ChannableProductResource::collection(Product::publicShowable()->get()));
            //            return json_encode(ChannableProductResource::collection(Product::publicShowable()->limit(100)->get()));
        } else {
            return json_encode([]);
        }
    }
}
