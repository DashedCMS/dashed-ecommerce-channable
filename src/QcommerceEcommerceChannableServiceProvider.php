<?php

namespace Qubiqx\QcommerceEcommerceChannable;

use Filament\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Qubiqx\QcommerceEcommerceChannable\Filament\Pages\Settings\ChannableSettingsPage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Qubiqx\QcommerceEcommerceChannable\Commands\QcommerceEcommerceChannableCommand;

class QcommerceEcommerceChannableServiceProvider extends PluginServiceProvider
{
    public static string $name = 'qcommerce-ecommerce-channable';

    public function bootingPackage()
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);
//            $schedule->command(PushOrdersToEboekhoudenCommand::class)->everyFifteenMinutes();
        });

//        Livewire::component('show-eboekhouden-order', ShowEboekhoudenShopOrder::class);

//        Order::addDynamicRelation('eboekhoudenOrder', function (Order $model) {
//            return $model->hasOne(EboekhoudenOrder::class);
//        });
    }

    public function configurePackage(Package $package): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        cms()->builder(
            'settingPages',
            array_merge(cms()->builder('settingPages'), [
                'eboekhouden' => [
                    'name' => 'Channable',
                    'description' => 'Koppel Channable',
                    'icon' => 'archive',
                    'page' => EboekhoudenSettingsPage::class,
                ],
            ])
        );

//        ecommerce()->builder(
//            'orderSideWidgets',
//            array_merge(ecommerce()->builder('orderSideWidgets'), [
//                'show-eboekhouden-order' => [
//                    'name' => 'show-eboekhouden-order',
//                ],
//            ])
//        );

        $package
            ->name('qcommerce-ecommerce-channable')
            ->hasViews()
            ->hasCommands([
//                PushOrdersToEboekhoudenCommand::class,
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
//        return array_merge(parent::getWidgets(), [
//            EboekhoudenOrderStats::class,
//        ]);
    }
}
