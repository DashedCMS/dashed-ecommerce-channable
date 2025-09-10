<?php

namespace Dashed\DashedEcommerceChannable;

use Spatie\LaravelPackageTools\Package;
use Illuminate\Console\Scheduling\Schedule;
use Dashed\DashedEcommerceCore\Models\Order;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Dashed\DashedEcommerceChannable\Models\ChannableOrder;
use Dashed\DashedEcommerceChannable\Commands\CreateJSONFeedsCommand;
use Dashed\DashedEcommerceChannable\Commands\SyncStockFromChannableCommand;
use Dashed\DashedEcommerceChannable\Commands\SyncOrdersFromChannableCommand;
use Dashed\DashedEcommerceChannable\Filament\Pages\Settings\ChannableSettingsPage;

class DashedEcommerceChannableServiceProvider extends PackageServiceProvider
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
    }
}
