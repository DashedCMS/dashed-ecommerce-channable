<?php

namespace Dashed\DashedEcommerceChannable;

use Spatie\LaravelPackageTools\Package;
use Illuminate\Console\Scheduling\Schedule;
use Dashed\DashedEcommerceCore\Models\Order;
use Dashed\DashedCore\Support\MeasuresServiceProvider;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Dashed\DashedEcommerceChannable\Models\ChannableOrder;
use Dashed\DashedEcommerceChannable\Commands\CreateJSONFeedsCommand;
use Dashed\DashedEcommerceChannable\Commands\SyncStockFromChannableCommand;
use Dashed\DashedEcommerceChannable\Commands\SyncOrdersFromChannableCommand;
use Dashed\DashedEcommerceChannable\Filament\Pages\Settings\ChannableSettingsPage;

class DashedEcommerceChannableServiceProvider extends PackageServiceProvider
{
    use MeasuresServiceProvider;
    public static string $name = 'dashed-ecommerce-channable';

    public function bootingPackage()
    {
        $this->logProviderMemory('bootingPackage:start');
        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(SyncOrdersFromChannableCommand::class)->everyFifteenMinutes();
            $schedule->command(SyncStockFromChannableCommand::class)->everyFifteenMinutes();
            $schedule->command(CreateJSONFeedsCommand::class)->hourly();
        });

        Order::addDynamicRelation('channableOrder', function (Order $model) {
            return $model->hasOne(ChannableOrder::class);
        });
        $this->logProviderMemory('bootingPackage:end');
    }

    public function configurePackage(Package $package): void
    {
        $this->logProviderMemory('configurePackage:start');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        cms()->registerSettingsPage(ChannableSettingsPage::class, 'Channable', 'archive-box', 'Koppel Channable');

        $package
            ->name('dashed-ecommerce-channable')
//            ->hasViews()
            ->hasRoutes([
                'channableRoutes',
            ])
            ->hasCommands([
                CreateJSONFeedsCommand::class,
                SyncOrdersFromChannableCommand::class,
                SyncStockFromChannableCommand::class,
            ]);

        cms()->builder('plugins', [
            new DashedEcommerceChannablePlugin(),
        ]);
        $this->logProviderMemory('configurePackage:end');
    }
}
