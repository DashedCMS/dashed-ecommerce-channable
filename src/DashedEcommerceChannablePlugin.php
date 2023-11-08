<?php

namespace Dashed\DashedEcommerceChannable;

use Dashed\DashedEcommerceChannable\Filament\Pages\Settings\ChannableSettingsPage;
use Dashed\DashedEcommerceChannable\Filament\Widgets\ChannableOrderStats;
use Filament\Contracts\Plugin;
use Filament\Panel;

class DashedEcommerceChannablePlugin implements Plugin
{
    public function getId(): string
    {
        return 'dashed-ecommerce-channable';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->widgets([
                ChannableOrderStats::class,
            ])
            ->pages([
                ChannableSettingsPage::class,
            ]);
    }

    public function boot(Panel $panel): void
    {

    }
}
