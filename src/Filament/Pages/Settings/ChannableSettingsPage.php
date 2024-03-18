<?php

namespace Dashed\DashedEcommerceChannable\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Forms\Components\Tabs;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
<<<<<<< HEAD
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedEcommerceChannable\Classes\Channable;
=======
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
>>>>>>> filamentv3

class ChannableSettingsPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Channable';

    protected static string $view = 'dashed-core::settings.pages.default-settings';
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

    protected function getFormSchema(): array
    {
        $sites = Sites::getSites();
        $tabGroups = [];

        $tabs = [];
        foreach ($sites as $site) {
            $schema = [
                Placeholder::make('label')
                    ->label("Channable voor {$site['name']}")
                    ->content('Activeer Channable.')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                Placeholder::make('label')
                    ->label("Channable is " . (! Customsetting::get('channable_connected', $site['id'], 0) ? 'niet' : '') . ' geconnect')
                    ->content(Customsetting::get('channable_connection_error', $site['id'], ''))
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
                ->schema($schema)
                ->columns([
                    'default' => 1,
                    'lg' => 2,
                ]);
        }
        $tabGroups[] = Tabs::make('Sites')
            ->tabs($tabs);

        return $tabGroups;
    }

    public function getFormStatePath(): ?string
    {
        return 'data';
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
}
