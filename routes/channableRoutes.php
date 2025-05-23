<?php

use Illuminate\Support\Facades\Route;
use Dashed\DashedEcommerceChannable\Controllers\ChannableController;

Route::get('/channable-feed/{locale}', [ChannableController::class, 'index']);
