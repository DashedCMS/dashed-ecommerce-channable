<?php

namespace Dashed\DashedEcommerceChannable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Dashed\DashedEcommerceChannable\DashedEcommerceChannable
 */
class DashedEcommerceChannable extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'dashed-ecommerce-channable';
    }
}
