<?php

namespace Qubiqx\QcommerceEcommerceChannable\Filament\Pages\Settings;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Qubiqx\QcommerceCore\Classes\Sites;
use Qubiqx\QcommerceCore\Models\Customsetting;
use Qubiqx\QcommerceEcommerceEboekhouden\Classes\Eboekhouden;

class ChannableSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Channable';

    protected static string $view = 'qcommerce-core::settings.pages.default-settings';

    public function mount(): void
    {
        $formData = [];
        $sites = Sites::getSites();
        foreach ($sites as $site) {
            $formData["eboekhouden_username_{$site['id']}"] = Customsetting::get('eboekhouden_username', $site['id']);
            $formData["eboekhouden_security_code_1_{$site['id']}"] = Customsetting::get('eboekhouden_security_code_1', $site['id']);
            $formData["eboekhouden_security_code_2_{$site['id']}"] = Customsetting::get('eboekhouden_security_code_2', $site['id']);
            $formData["eboekhouden_grootboek_rekening_{$site['id']}"] = Customsetting::get('eboekhouden_grootboek_rekening', $site['id']);
            $formData["eboekhouden_debiteuren_rekening_{$site['id']}"] = Customsetting::get('eboekhouden_debiteuren_rekening', $site['id']);
            $formData["eboekhouden_connected_{$site['id']}"] = Customsetting::get('eboekhouden_connected', $site['id'], 0) ? true : false;
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
                    ->label("E-boekhouden voor {$site['name']}")
                    ->content('Activeer E-boekhouden.')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                Placeholder::make('label')
                    ->label("E-boekhouden is " . (! Customsetting::get('eboekhouden_connected', $site['id'], 0) ? 'niet' : '') . ' geconnect')
                    ->content(Customsetting::get('eboekhouden_connection_error', $site['id'], ''))
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                TextInput::make("eboekhouden_username_{$site['id']}")
                    ->label('E-boekhouden username')
                    ->rules([
                        'max:255',
                    ]),
                TextInput::make("eboekhouden_security_code_1_{$site['id']}")
                    ->label('E-boekhouden security code 1')
                    ->rules([
                        'max:255',
                    ]),
                TextInput::make("eboekhouden_security_code_2_{$site['id']}")
                    ->label('E-boekhouden security code 2')
                    ->rules([
                        'max:255',
                    ]),
                TextInput::make("eboekhouden_grootboek_rekening_{$site['id']}")
                    ->label('E-boekhouden grootboekrekening')
                    ->rules([
                        'max:255',
                    ]),
                TextInput::make("eboekhouden_debiteuren_rekening_{$site['id']}")
                    ->label('E-boekhouden debiteurenrekening')
                    ->rules([
                        'max:255',
                    ]),
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

    public function submit()
    {
        $sites = Sites::getSites();

        foreach ($sites as $site) {
            Customsetting::set('eboekhouden_username', $this->form->getState()["eboekhouden_username_{$site['id']}"], $site['id']);
            Customsetting::set('eboekhouden_security_code_1', $this->form->getState()["eboekhouden_security_code_1_{$site['id']}"], $site['id']);
            Customsetting::set('eboekhouden_security_code_2', $this->form->getState()["eboekhouden_security_code_2_{$site['id']}"], $site['id']);
            Customsetting::set('eboekhouden_grootboek_rekening', $this->form->getState()["eboekhouden_grootboek_rekening_{$site['id']}"], $site['id']);
            Customsetting::set('eboekhouden_debiteuren_rekening', $this->form->getState()["eboekhouden_debiteuren_rekening_{$site['id']}"], $site['id']);
            Customsetting::set('eboekhouden_connected', Eboekhouden::isConnected($site['id']), $site['id']);
        }

        $this->notify('success', 'De E-boekhouden instellingen zijn opgeslagen');

        return redirect(EboekhoudenSettingsPage::getUrl());
    }
}
