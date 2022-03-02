<?php

use Illuminate\Support\Facades\Route;
use Qubiqx\QcommerceEcommerceChannable\Controllers\ChannableController;

if (!app()->runningInConsole()) {
    Route::get('/channable-feed/{locale}', [ChannableController::class, 'index']);
}
