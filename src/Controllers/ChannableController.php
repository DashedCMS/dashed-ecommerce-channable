<?php

namespace Dashed\DashedEcommerceChannable\Controllers;

use Illuminate\Support\Facades\App;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Support\Facades\Storage;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedEcommerceCore\Models\Product;
use Dashed\DashedCore\Controllers\Frontend\FrontendController;
use Dashed\DashedEcommerceChannable\Resources\ChannableProductResource;

class ChannableController extends FrontendController
{
    public function index($locale = null)
    {
        if (Customsetting::get('channable_feed_enabled') == 1) {
            $locale = Locales::getLocale($locale);
            App::setLocale($locale['id']);

            $contents = Storage::disk('dashed')->get('/channable-feeds/channable-feed-' . $locale['id'] . '.json');
            return json_decode($contents, true);

            return json_encode(ChannableProductResource::collection(Product::publicShowable()->get()));
            //            return json_encode(ChannableProductResource::collection(Product::publicShowable()->limit(100)->get()));
        } else {
            return json_encode([]);
        }
    }
}
