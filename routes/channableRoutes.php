<?php

use Illuminate\Support\Facades\Route;
use Dashed\DashedEcommerceChannable\Controllers\ChannableController;

if (!app()->runningInConsole()) {
    Route::get('/channable-feed/{locale}', [ChannableController::class, 'index']);
}
