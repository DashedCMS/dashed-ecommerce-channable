<?php

namespace Dashed\DashedEcommerceChannable;

use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Illuminate\Console\Scheduling\Schedule;
use Dashed\DashedEcommerceCore\Models\Order;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Dashed\DashedEcommerceChannable\Models\ChannableOrder;
use Dashed\DashedEcommerceChannable\Jobs\CreateJSONFeedsJob;
use Dashed\DashedEcommerceChannable\Commands\CreateJSONFeedsCommand;
use Dashed\DashedEcommerceChannable\Commands\SyncStockFromChannableCommand;
use Dashed\DashedEcommerceChannable\Commands\SyncOrdersFromChannableCommand;
use Dashed\DashedEcommerceCore\Events\Products\ProductInformationUpdatedEvent;
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
            $schedule->command(CreateJSONFeedsCommand::class)->hourly();
        });

        Order::addDynamicRelation('channableOrder', function (Order $model) {
            return $model->hasOne(ChannableOrder::class);
        });

        Event::listen(ProductInformationUpdatedEvent::class, function () {
            CreateJSONFeedsJob::dispatch();
        });

        cms()->registerSettingsDocs(
            page: \Dashed\DashedEcommerceChannable\Filament\Pages\Settings\ChannableSettingsPage::class,
            title: 'Channable instellingen',
            intro: 'Koppel de webshop met Channable om productfeeds, bestellingen en voorraad te delen met marktplaatsen zoals bol en Amazon. Per site bepaal je welk Channable project gebruikt wordt en welke onderdelen van de koppeling actief zijn.',
            sections: [
                [
                    'heading' => 'Wat kun je hier instellen?',
                    'body' => <<<MARKDOWN
Op deze pagina regel je twee dingen:

1. De inloggegevens voor je Channable account: de API key, het company ID en het project ID.
2. Welke onderdelen van de koppeling aan staan: de productfeed, het synchroniseren van bestellingen en het synchroniseren van voorraad.

Een webshop kan in Channable meerdere projecten hebben. Het project ID bepaalt welk project bij deze site hoort.
MARKDOWN,
                ],
                [
                    'heading' => 'Hoe zet je dit op?',
                    'body' => <<<MARKDOWN
1. Log in op het Channable dashboard.
2. Open het project dat bij deze webshop hoort.
3. Ga naar de projectinstellingen en open het onderdeel voor de API.
4. Kopieer de API key, het company ID en het project ID.
5. Plak deze drie waarden in de velden hieronder.
6. Bepaal welke onderdelen je aan wilt zetten: productfeed, bestellingen sync en voorraad sync.
7. Sla de instellingen op.
MARKDOWN,
                ],
                [
                    'heading' => 'Wat doen de drie schakelaars?',
                    'body' => <<<MARKDOWN
- **Productfeed**: hiermee maakt de webshop een feed beschikbaar die Channable gebruikt om producten naar marktplaatsen te sturen.
- **Bestellingen sync**: bestellingen die via Channable binnenkomen (bijvoorbeeld vanaf bol) worden automatisch in deze webshop aangemaakt.
- **Voorraad sync**: zodra de voorraad in de webshop wijzigt, geeft de webshop dit door aan Channable, zodat marktplaatsen niet meer kunnen verkopen wat niet meer op voorraad is.

Je kunt ze los van elkaar aan en uit zetten, afhankelijk van hoe je de koppeling wilt gebruiken.
MARKDOWN,
                ],
            ],
            fields: [
                'API key' => 'De API key van je Channable account. Hiermee mag de webshop verbinding maken met Channable.',
                'Company ID' => 'Het bedrijfs ID in Channable. Dit hoort bij je organisatie en blijft hetzelfde voor al je projecten.',
                'Project ID' => 'Het ID van het Channable project dat bij deze site hoort. Heb je meerdere projecten? Kies dan het juiste project, anders worden de verkeerde producten en bestellingen gekoppeld.',
                'Productfeed actief' => 'Zet aan om de productfeed beschikbaar te maken voor Channable. Channable haalt de feed dan op en stuurt de producten door naar de marktplaatsen.',
                'Bestellingen synchroniseren' => 'Bestellingen die via Channable binnenkomen worden automatisch in deze webshop aangemaakt zodat je ze net als gewone bestellingen kunt verwerken.',
                'Voorraad synchroniseren' => 'De webshop stuurt voorraadwijzigingen door naar Channable. Zo blijven de aantallen op marktplaatsen actueel.',
            ],
            tips: [
                'Controleer goed of het project ID bij de juiste site hoort. Een verkeerd project ID is de meest voorkomende oorzaak van producten die op de verkeerde marktplaats verschijnen.',
                'Zet voorraad sync aan zodra je begint met verkopen via marktplaatsen. Anders kun je dingen verkopen die je niet meer hebt liggen.',
                'Test de koppeling eerst met een paar producten en een proefbestelling, zodat je zeker weet dat de gegevens netjes heen en weer worden gestuurd.',
                'Bewaar de API key veilig en deel hem niet. Iemand met deze sleutel kan namens jou wijzigingen in Channable aanvragen.',
            ],
        );
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
