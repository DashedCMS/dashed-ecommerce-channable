<?php

namespace Qubiqx\QcommerceEcommerceChannable;

use Filament\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Qubiqx\QcommerceEcommerceChannable\Commands\CreateJSONFeedsCommand;
use Qubiqx\QcommerceEcommerceChannable\Commands\SyncOrdersFromChannableCommand;
use Qubiqx\QcommerceEcommerceChannable\Commands\SyncStockFromChannableCommand;
use Qubiqx\QcommerceEcommerceChannable\Filament\Pages\Settings\ChannableSettingsPage;
use Qubiqx\QcommerceEcommerceChannable\Filament\Widgets\ChannableOrderStats;
use Qubiqx\QcommerceEcommerceChannable\Models\ChannableOrder;
use Qubiqx\QcommerceEcommerceCore\Models\Order;
use Spatie\LaravelPackageTools\Package;

class QcommerceEcommerceChannableServiceProvider extends PluginServiceProvider
{
    public static string $name = 'qcommerce-ecommerce-channable';

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
            ->name('qcommerce-ecommerce-channable')
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
