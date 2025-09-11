<?php

namespace Dashed\DashedEcommerceChannable;

use Dashed\DashedEcommerceChannable\Models\ChannableOrder;
use Filament\Panel;
use Filament\Contracts\Plugin;
use Dashed\DashedEcommerceChannable\Filament\Widgets\ChannableOrderStats;
use Dashed\DashedEcommerceChannable\Filament\Pages\Settings\ChannableSettingsPage;

class DashedEcommerceChannablePlugin implements Plugin
{
    public function getId(): string
    {
        return 'dashed-ecommerce-channable';
    }

    public function register(Panel $panel): void
    {
        $widgets = [];

        if(ChannableOrder::count()){
            $widgets[] = ChannableOrderStats::class;
        }

        $panel
            ->widgets($widgets)
            ->pages([
                ChannableSettingsPage::class,
            ]);
    }

    public function boot(Panel $panel): void
    {

    }
}
