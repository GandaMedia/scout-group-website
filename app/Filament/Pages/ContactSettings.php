<?php

namespace App\Filament\Pages;

use App\Filament\AdminNavigationGroup;
use App\Settings\ContactSettings as ContactSettingsStore;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ContactSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Settings;

    protected static ?string $navigationLabel = 'Contact settings';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.contact-settings';

    public ?array $data = [];

    public function mount(ContactSettingsStore $contactSettings): void
    {
        $this->form->fill([
            'success_message' => $contactSettings->success_message,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('success_message')
                    ->label('Success message')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(ContactSettingsStore $contactSettings): void
    {
        $state = $this->form->getState();

        $contactSettings->success_message = $state['success_message'];
        $contactSettings->save();

        Notification::make()
            ->title('Contact settings saved')
            ->success()
            ->send();
    }
}
