<?php

namespace Dashed\DashedEcommerceChannable\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Actions\ActionGroup;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Illuminate\Support\Facades\Cache;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs\Tab;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Infolists\Components\TextEntry;
use Dashed\DashedCore\Traits\HasSettingsPermission;
use Dashed\DashedEcommerceCore\Models\ProductGroup;
use Dashed\DashedEcommerceChannable\Classes\Channable;
use Dashed\DashedEcommerceChannable\Classes\FeedProgress;
use Dashed\DashedEcommerceChannable\Jobs\CreateJSONFeedsJob;
use Dashed\DashedEcommerceCore\Jobs\UpdateProductInformationJob;

class ChannableSettingsPage extends Page
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;
    use HasSettingsPermission;

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Channable';

    protected string $view = 'dashed-ecommerce-channable::settings-page';
    public array $data = [];

    public function mount(): void
    {
        $formData = [];
        $sites = Sites::getSites();
        foreach ($sites as $site) {
            $formData["channable_api_key_{$site['id']}"] = Customsetting::get('channable_api_key', $site['id']);
            $formData["channable_company_id_{$site['id']}"] = Customsetting::get('channable_company_id', $site['id']);
            $formData["channable_project_id_{$site['id']}"] = Customsetting::get('channable_project_id', $site['id']);
            $formData["channable_feed_enabled_{$site['id']}"] = Customsetting::get('channable_feed_enabled', $site['id'], 0) ? true : false;
            $formData["channable_order_sync_enabled_{$site['id']}"] = Customsetting::get('channable_order_sync_enabled', $site['id'], 0) ? true : false;
            $formData["channable_stock_sync_enabled_{$site['id']}"] = Customsetting::get('channable_stock_sync_enabled', $site['id'], 0) ? true : false;
            $formData["channable_connected_{$site['id']}"] = Customsetting::get('channable_connected', $site['id'], 0) ? true : false;
        }

        $this->form->fill($formData);
    }

    public function form(Schema $schema): Schema
    {
        $sites = Sites::getSites();
        $tabGroups = [];

        $tabs = [];
        foreach ($sites as $site) {
            $newSchema = [
                TextEntry::make("Channable voor {$site['name']}")
                    ->state('Activeer Channable.')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                TextEntry::make("Channable is " . (! Customsetting::get('channable_connected', $site['id'], 0) ? 'niet' : '') . ' geconnect')
                    ->state(Customsetting::get('channable_connection_error', $site['id'], ''))
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                TextInput::make("channable_api_key_{$site['id']}")
                    ->label(__('Channable API key'))
                    ->maxLength(255),
                TextInput::make("channable_company_id_{$site['id']}")
                    ->label(__('Channable company ID'))
                    ->maxLength(255),
                TextInput::make("channable_project_id_{$site['id']}")
                    ->label(__('Channable project ID'))
                    ->maxLength(255),
                Toggle::make("channable_feed_enabled_{$site['id']}")
                    ->label(__('Channable feed aanzetten')),
                Toggle::make("channable_order_sync_enabled_{$site['id']}")
                    ->label(__('Order uit Channable naar webshop syncen')),
                Toggle::make("channable_stock_sync_enabled_{$site['id']}")
                    ->label(__('Voorraad vanuit webshop naar Channable syncen')),
            ];

            $tabs[] = Tab::make($site['id'])
                ->label(ucfirst($site['name']))
                ->schema($newSchema)
                ->columns([
                    'default' => 1,
                    'lg' => 2,
                ]);
        }
        $tabGroups[] = Tabs::make('Sites')
            ->tabs($tabs);

        return $schema->schema($tabGroups)
            ->statePath('data');
    }

    public function submit()
    {
        $sites = Sites::getSites();

        foreach ($sites as $site) {
            Customsetting::set('channable_api_key', $this->form->getState()["channable_api_key_{$site['id']}"], $site['id']);
            Customsetting::set('channable_company_id', $this->form->getState()["channable_company_id_{$site['id']}"], $site['id']);
            Customsetting::set('channable_project_id', $this->form->getState()["channable_project_id_{$site['id']}"], $site['id']);
            Customsetting::set('channable_feed_enabled', $this->form->getState()["channable_feed_enabled_{$site['id']}"], $site['id']);
            Customsetting::set('channable_order_sync_enabled', $this->form->getState()["channable_order_sync_enabled_{$site['id']}"], $site['id']);
            Customsetting::set('channable_stock_sync_enabled', $this->form->getState()["channable_stock_sync_enabled_{$site['id']}"], $site['id']);
            Customsetting::set('channable_connected', Channable::isConnected($site['id']), $site['id']);
        }

        Notification::make()
            ->title(__('De Channable instellingen zijn opgeslagen'))
            ->success()
            ->send();

        return redirect(ChannableSettingsPage::getUrl());
    }

    public function getFeedProgress(): ?array
    {
        $progress = FeedProgress::get();

        if (! $progress) {
            return null;
        }

        return array_merge($progress, [
            'active' => FeedProgress::isActive($progress),
            'stale' => FeedProgress::isStale($progress),
            'percentage' => FeedProgress::percentage($progress),
        ]);
    }

    public function getFeedFiles(): array
    {
        // Cached briefly: the page polls while a feed is being written.
        return Cache::remember('dashed-channable-feed-files', 30, function () {
            $disk = Storage::disk('dashed');
            $files = [];

            foreach (Locales::getLocales() as $locale) {
                $path = "channable-feeds/channable-feed-{$locale['id']}.json";

                try {
                    $updatedAt = $disk->exists($path) ? $disk->lastModified($path) : null;
                } catch (\Throwable) {
                    $updatedAt = null;
                }

                $files[] = [
                    'locale' => ucfirst($locale['name']),
                    'updated_at' => $updatedAt,
                ];
            }

            return $files;
        });
    }

    protected function getActions(): array
    {
        $jsonFeedActions = [];

        foreach (Locales::getLocales() as $locale) {
            $jsonFeedActions[] = Action::make("openJsonFeed_{$locale['id']}")
                ->label(ucfirst($locale['name']))
                ->url(route('dashed.channable-feed', ['locale' => $locale['id']]))
                ->openUrlInNewTab()
                ->button()
                ->icon('heroicon-o-link');
        }

        return [
            Action::make('refreshJsonFeed')
                ->label(__('Refresh JSON feed'))
                ->disabled(fn () => FeedProgress::isActive())
                ->action(function () {
                    FeedProgress::queue();
                    CreateJSONFeedsJob::dispatch(true)->onQueue('ecommerce');

                    Notification::make()
                        ->title(__('De JSON feed wordt op de achtergrond vernieuwd'))
                        ->success()
                        ->send();
                })
                ->icon('heroicon-o-arrow-path')
                ->color('primary'),
            Action::make('refreshJsonFeedWithProducts')
                ->label(__('Refresh JSON feed met producten'))
                ->disabled(fn () => FeedProgress::isActive())
                ->action(function () {
                    $productGroups = ProductGroup::all();

                    // The feed itself is written after the last product group, see the service provider.
                    FeedProgress::startWithProducts($productGroups->count());

                    foreach ($productGroups as $productGroup) {
                        UpdateProductInformationJob::dispatch($productGroup, false)->onQueue('ecommerce');
                    }

                    if ($productGroups->isEmpty()) {
                        CreateJSONFeedsJob::dispatch(true)->onQueue('ecommerce');
                    }

                    Notification::make()
                        ->title(__('De JSON feed wordt op de achtergrond vernieuwd'))
                        ->success()
                        ->send();
                })
                ->icon('heroicon-o-arrow-path')
                ->color('primary'),
            ActionGroup::make($jsonFeedActions)
                ->button()
                ->icon('heroicon-o-eye')
                ->label(__('Open JSON feed')),
        ];
    }
}
