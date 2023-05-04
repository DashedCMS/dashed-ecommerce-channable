<?php

namespace Qubiqx\QcommerceEcommerceChannable\Controllers;

use Illuminate\Support\Facades\App;
use Qubiqx\QcommerceCore\Classes\Locales;
use Qubiqx\QcommerceCore\Controllers\Frontend\FrontendController;
use Qubiqx\QcommerceCore\Models\Customsetting;
use Qubiqx\QcommerceEcommerceChannable\Resources\ChannableProductResource;
use Qubiqx\QcommerceEcommerceCore\Models\Product;

class ChannableController extends FrontendController
{
    public function index($locale = null)
    {
        if (Customsetting::get('channable_feed_enabled') == 1) {
            $locale = Locales::getLocale($locale);
            App::setLocale($locale['id']);

            return json_encode(ChannableProductResource::collection(Product::publicShowable()->limit(100)->get()));
        } else {
            return json_encode([]);
        }
    }
}
