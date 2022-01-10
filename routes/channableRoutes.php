<?php

use Illuminate\Support\Facades\Route;
use Qubiqx\QcommerceCore\Middleware\AdminMiddleware;
use Qubiqx\QcommerceEcommerceChannable\Controllers\ChannableController;
use Qubiqx\QcommerceEcommerceKeendelivery\Controllers\KeendeliveryController;

if (!app()->runningInConsole()) {
    Route::get('/channable-feed/{locale}', [ChannableController::class, 'index']);
}
