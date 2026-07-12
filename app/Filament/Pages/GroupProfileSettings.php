<?php

namespace App\Filament\Pages;

use App\Filament\AdminNavigationGroup;
use App\Settings\GroupProfileSettings as GroupProfileSettingsStore;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class GroupProfileSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Settings;

    protected static ?string $navigationLabel = 'Group profile';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.group-profile-settings';

    public ?array $data = [];

    public function mount(GroupProfileSettingsStore $groupProfileSettings): void
    {
        $this->form->fill([
            'group_name' => $groupProfileSettings->group_name,
            'group_short_name' => $groupProfileSettings->group_short_name,
            'logo_short_label' => $groupProfileSettings->logo_short_label,
            'logo_stacked_line_1' => $groupProfileSettings->logo_stacked_line_1,
            'logo_stacked_line_2' => $groupProfileSettings->logo_stacked_line_2,
            'website_url' => $groupProfileSettings->website_url,
            'mail_from_name' => $groupProfileSettings->mail_from_name,
            'mail_from_address' => $groupProfileSettings->mail_from_address,
            'contact_recipient_name' => $groupProfileSettings->contact_recipient_name,
            'contact_recipient_email' => $groupProfileSettings->contact_recipient_email,
            'headquarters_label' => $groupProfileSettings->headquarters_label,
            'headquarters_address' => $groupProfileSettings->headquarters_address,
            'map_embed_url' => $groupProfileSettings->map_embed_url,
            'charity_number' => $groupProfileSettings->charity_number,
            'charity_register_url' => $groupProfileSettings->charity_register_url,
            'district_name' => $groupProfileSettings->district_name,
            'district_url' => $groupProfileSettings->district_url,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->schema([
                        TextInput::make('group_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('group_short_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('website_url')
                            ->label('Website URL')
                            ->required()
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Logo text')
                    ->schema([
                        TextInput::make('logo_short_label')
                            ->label('Horizontal logo label')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('logo_stacked_line_1')
                            ->label('Stacked logo line 1')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('logo_stacked_line_2')
                            ->label('Stacked logo line 2')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Email')
                    ->schema([
                        TextInput::make('mail_from_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('mail_from_address')
                            ->required()
                            ->email()
                            ->maxLength(255),
                        TextInput::make('contact_recipient_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('contact_recipient_email')
                            ->required()
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Headquarters')
                    ->schema([
                        TextInput::make('headquarters_label')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('headquarters_address')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        TextInput::make('map_embed_url')
                            ->label('Google Maps embed URL')
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Footer')
                    ->schema([
                        TextInput::make('charity_number')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('charity_register_url')
                            ->label('Charity register URL')
                            ->required()
                            ->url()
                            ->maxLength(2048),
                        TextInput::make('district_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('district_url')
                            ->label('District URL')
                            ->required()
                            ->url()
                            ->maxLength(2048),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(GroupProfileSettingsStore $groupProfileSettings): void
    {
        $state = $this->form->getState();

        $groupProfileSettings->group_name = $state['group_name'];
        $groupProfileSettings->group_short_name = $state['group_short_name'];
        $groupProfileSettings->logo_short_label = $state['logo_short_label'];
        $groupProfileSettings->logo_stacked_line_1 = $state['logo_stacked_line_1'];
        $groupProfileSettings->logo_stacked_line_2 = $state['logo_stacked_line_2'];
        $groupProfileSettings->website_url = $state['website_url'];
        $groupProfileSettings->mail_from_name = $state['mail_from_name'];
        $groupProfileSettings->mail_from_address = $state['mail_from_address'];
        $groupProfileSettings->contact_recipient_name = $state['contact_recipient_name'];
        $groupProfileSettings->contact_recipient_email = $state['contact_recipient_email'];
        $groupProfileSettings->headquarters_label = $state['headquarters_label'];
        $groupProfileSettings->headquarters_address = $state['headquarters_address'];
        $groupProfileSettings->map_embed_url = $state['map_embed_url'] ?? '';
        $groupProfileSettings->charity_number = $state['charity_number'];
        $groupProfileSettings->charity_register_url = $state['charity_register_url'];
        $groupProfileSettings->district_name = $state['district_name'];
        $groupProfileSettings->district_url = $state['district_url'];
        $groupProfileSettings->save();

        Notification::make()
            ->title('Group profile saved')
            ->success()
            ->send();
    }
}
