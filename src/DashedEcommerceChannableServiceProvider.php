<?php

namespace Dashed\DashedEcommerceChannable;

use Filament\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Dashed\DashedEcommerceChannable\Commands\CreateJSONFeedsCommand;
use Dashed\DashedEcommerceChannable\Commands\SyncOrdersFromChannableCommand;
use Dashed\DashedEcommerceChannable\Commands\SyncStockFromChannableCommand;
use Dashed\DashedEcommerceChannable\Filament\Pages\Settings\ChannableSettingsPage;
use Dashed\DashedEcommerceChannable\Filament\Widgets\ChannableOrderStats;
use Dashed\DashedEcommerceChannable\Models\ChannableOrder;
use Dashed\DashedEcommerceCore\Models\Order;
use Spatie\LaravelPackageTools\Package;

class DashedEcommerceChannableServiceProvider extends PluginServiceProvider
{
    public static string $name = 'dashed-ecommerce-channable';

    public function bootingPackage()
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(SyncOrdersFromChannableCommand::class)->everyFifteenMinutes();
            $schedule->command(SyncStockFromChannableCommand::class)->everyFifteenMinutes();
            $schedule->command(CreateJSONFeedsCommand::class)->everyFifteenMinutes();
        });

        Order::addDynamicRelation('channableOrder', function (Order $model) {
            return $model->hasOne(ChannableOrder::class);
        });
    }

    public function configurePackage(Package $package): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        cms()->builder(
            'settingPages',
            array_merge(cms()->builder('settingPages'), [
                'channable' => [
                    'name' => 'Channable',
                    'description' => 'Koppel Channable',
                    'icon' => 'archive',
                    'page' => ChannableSettingsPage::class,
                ],
            ])
        );

        $package
            ->name('dashed-ecommerce-channable')
            ->hasViews()
            ->hasRoutes([
                'channableRoutes',
            ])
            ->hasCommands([
                CreateJSONFeedsCommand::class,
                SyncOrdersFromChannableCommand::class,
                SyncStockFromChannableCommand::class,
            ]);
    }

    protected function getPages(): array
    {
        return array_merge(parent::getPages(), [
            ChannableSettingsPage::class,
        ]);
    }

    protected function getWidgets(): array
    {
        return array_merge(parent::getWidgets(), [
            ChannableOrderStats::class,
        ]);
    }
}
