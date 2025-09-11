<?php

namespace Dashed\DashedEcommerceChannable\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Dashed\DashedEcommerceChannable\Models\ChannableOrder;

class ChannableOrderStats extends StatsOverviewWidget
{
    protected function getCards(): array
    {
        return [
            StatsOverviewWidget\Stat::make('Aantal bestellingen vanuit Channable', ChannableOrder::count()),
        ];
    }
}
