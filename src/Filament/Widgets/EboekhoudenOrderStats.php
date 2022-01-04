<?php

namespace Qubiqx\QcommerceEcommerceEboekhouden\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Qubiqx\QcommerceEcommerceEboekhouden\Models\EboekhoudenOrder;

class EboekhoudenOrderStats extends StatsOverviewWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Aantal bestellingen naar E-boekhouden', EboekhoudenOrder::where('pushed', 1)->count()),
            Card::make('Aantal bestellingen in de wacht', EboekhoudenOrder::where('pushed', 0)->count()),
            Card::make('Aantal bestellingen gefaald', EboekhoudenOrder::where('pushed', 2)->count()),
        ];
    }
}
