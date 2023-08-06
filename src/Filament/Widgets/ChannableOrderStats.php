<?php

namespace Dashed\DashedEcommerceChannable\Filament\Widgets;

use Dashed\DashedEcommerceChannable\Models\ChannableOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class ChannableOrderStats extends StatsOverviewWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Aantal bestellingen vanuit Channable', ChannableOrder::count()),
        ];
    }
}
