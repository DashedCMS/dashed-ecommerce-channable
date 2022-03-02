<?php

namespace Qubiqx\QcommerceEcommerceChannable\Controllers;

use Illuminate\Support\Facades\App;
use Qubiqx\QcommerceCore\Classes\Locales;
use Qubiqx\QcommerceCore\Models\Customsetting;
use Qubiqx\QcommerceEcommerceCore\Models\Product;
use Qubiqx\QcommerceCore\Controllers\Frontend\FrontendController;
use Qubiqx\QcommerceEcommerceChannable\Resources\ChannableProductResource;

class ChannableController extends FrontendController
{
    public function index($locale = null)
    {
        if (Customsetting::get('channable_feed_enabled') == 1) {
            $locale = Locales::getLocale($locale);
            App::setLocale($locale['id']);

            return json_encode(ChannableProductResource::collection(Product::publicShowable()->get()));
        } else {
            return json_encode([]);
        }
    }
}
