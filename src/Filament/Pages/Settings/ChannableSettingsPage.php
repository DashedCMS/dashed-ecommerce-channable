<?php

namespace Dashed\DashedEcommerceChannable\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Illuminate\Support\Facades\Artisan;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs\Tab;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Infolists\Components\TextEntry;
use Dashed\DashedEcommerceChannable\Classes\Channable;

class ChannableSettingsPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Channable';

    protected string $view = 'dashed-core::settings.pages.default-settings';
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
                    ->label('Channable API key')
                    ->maxLength(255),
                TextInput::make("channable_company_id_{$site['id']}")
                    ->label('Channable company ID')
                    ->maxLength(255),
                TextInput::make("channable_project_id_{$site['id']}")
                    ->label('Channable project ID')
                    ->maxLength(255),
                Toggle::make("channable_feed_enabled_{$site['id']}")
                    ->label('Channable feed aanzetten'),
                Toggle::make("channable_order_sync_enabled_{$site['id']}")
                    ->label('Order uit Channable naar webshop syncen'),
                Toggle::make("channable_stock_sync_enabled_{$site['id']}")
                    ->label('Voorraad vanuit webshop naar Channable syncen'),
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
            ->title('De Channable instellingen zijn opgeslagen')
            ->success()
            ->send();

        return redirect(ChannableSettingsPage::getUrl());
    }

    protected function getActions(): array
    {
        return [
          Action::make('refreshJsonFeed')
            ->label('Refresh JSON feed')
            ->action(function () {
                Artisan::call('channable:create-json-feeds');

                Notification::make()
                    ->title('De JSON feed is vernieuwd')
                    ->success()
                    ->send();
            })
            ->icon('heroicon-o-arrow-path')
            ->color('primary'),
        ];
    }
}
