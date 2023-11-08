<?php

namespace Dashed\DashedEcommerceChannable;

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
