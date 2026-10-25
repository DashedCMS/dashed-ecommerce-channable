<?php

namespace Dashed\DashedEcommerceChannable\Controllers;

use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Support\Facades\Storage;
use Dashed\DashedCore\Models\Customsetting;
use Symfony\Component\HttpFoundation\Response;

class ChannableController extends Controller
{
    public function index($locale = null): Response
    {
        if (Customsetting::get('channable_feed_enabled') != 1) {
            return response()->json([]);
        }

        $locale = Locales::getLocale($locale);
        App::setLocale($locale['id']);

        $disk = Storage::disk('dashed');
        $path = '/channable-feeds/channable-feed-' . $locale['id'] . '.json';

        $stream = rescue(fn () => $disk->readStream($path), null, false);

        if (! is_resource($stream)) {
            return response()->json([]);
        }

        return response()->stream(function () use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, 200, [
            'Content-Type' => 'application/json',
        ]);
    }
}
