<?php

namespace App\Filament\Pages;

use App\Filament\AdminNavigationGroup;
use App\Settings\HomePageSettings as HomePageSettingsStore;
use BackedEnum;
use Filament\Forms\Components\Repeater;
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

class HomePageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Settings;

    protected static ?string $navigationLabel = 'Home page settings';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.home-page-settings';

    public ?array $data = [];

    public function mount(HomePageSettingsStore $homePageSettings): void
    {
        $this->form->fill([
            'section_cards' => $homePageSettings->section_cards,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Section cards')
                    ->description('Manage the age-group cards shown on the home page.')
                    ->schema([
                        Repeater::make('section_cards')
                            ->label('Cards')
                            ->schema([
                                TextInput::make('section')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('page_slug')
                                    ->label('Page slug')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('age_range')
                                    ->label('Age range')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('time_slot')
                                    ->label('Time slot')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->required()
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->addable(false)
                            ->deletable(false)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(HomePageSettingsStore $homePageSettings): void
    {
        $state = $this->form->getState();

        $homePageSettings->section_cards = array_values($state['section_cards']);
        $homePageSettings->save();

        Notification::make()
            ->title('Home page settings saved')
            ->success()
            ->send();
    }
}
