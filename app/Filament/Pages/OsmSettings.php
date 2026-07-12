<?php

namespace App\Filament\Pages;

use App\Enums\Section as ScoutSection;
use App\Filament\AdminNavigationGroup;
use App\Jobs\ImportOsmSectionLeaders;
use App\Jobs\RefreshOsmDirectorySnapshot;
use App\Services\WaitingList\Osm\OsmAuthenticatorManager;
use App\Settings\OsmSettings as OsmSettingsStore;
use App\Settings\SectionSettings as SectionSettingsStore;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class OsmSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Settings;

    protected static ?string $title = 'OSM settings';

    protected static ?string $navigationLabel = 'OSM settings';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.pages.osm-settings';

    public ?array $data = [];

    public string $oauthClientId = '';

    public string $redirectUri = '';

    public string $requestedScopes = '';

    public bool $oauthConfigured = false;

    public bool $connected = false;

    public ?string $connectedAccount = null;

    public ?string $directoryError = null;

    public ?string $directoryRefreshedAt = null;

    public ?string $directoryRefreshQueuedAt = null;

    public function mount(OsmSettingsStore $osmSettings): void
    {
        $this->oauthClientId = (string) config('services.osm.client_id', '');
        $this->redirectUri = (string) config('services.osm.redirect_uri', '');
        $this->requestedScopes = implode(', ', config('services.osm.scopes', []));
        $this->oauthConfigured = filled($this->oauthClientId)
            && filled((string) config('services.osm.client_secret'))
            && filled($this->redirectUri);

        $this->form->fill([
            ...$this->publicSectionMappingState($osmSettings),
            'target_section_id' => $osmSettings->target_section_id,
            'target_term_id' => $osmSettings->target_term_id,
        ]);

        $this->hydrateDirectoryData($osmSettings);
        $this->dispatchMountNotification();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Waiting-list destination')
                    ->description('Choose the single OSM section and term that all website waiting-list submissions should sync into.')
                    ->schema([
                        Select::make('target_section_id')
                            ->label('OSM waiting-list section')
                            ->options(fn (): array => $this->sectionOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->disabled(fn (): bool => ! $this->connected || $this->sectionOptions() === [])
                            ->afterStateUpdated(fn (Set $set) => $set('target_term_id', null)),
                        Select::make('target_term_id')
                            ->label('OSM waiting-list term')
                            ->options(fn (Get $get): array => $this->termOptionsFor((string) $get('target_section_id')))
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get): bool => $this->sectionHasTerms((string) $get('target_section_id')))
                            ->helperText(fn (Get $get): ?string => $this->termHelperTextFor((string) $get('target_section_id')))
                            ->disabled(fn (Get $get): bool => blank($get('target_section_id'))),
                    ])
                    ->columns(2),
                Section::make('Public section sources')
                    ->description('Map each website section to its real OSM section. These mappings are separate from the waiting-list destination and will be used for section-specific imports.')
                    ->schema(fn (): array => $this->publicSectionMappingFields())
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connect')
                ->label($this->connected ? 'Reconnect OSM' : 'Connect OSM')
                ->icon(Heroicon::ArrowTopRightOnSquare)
                ->color('primary')
                ->url(route('osm.oauth.redirect')),
            Action::make('refreshDirectory')
                ->label('Refresh OSM data')
                ->icon(Heroicon::ArrowPath)
                ->visible($this->connected)
                ->action('refreshDirectoryData'),
            Action::make('seedLeaders')
                ->label('Import missing leaders')
                ->icon(Heroicon::UserPlus)
                ->visible($this->connected)
                ->action('seedLeaders'),
            Action::make('disconnect')
                ->label('Disconnect')
                ->icon(Heroicon::Power)
                ->color('danger')
                ->visible($this->connected)
                ->requiresConfirmation()
                ->action('disconnectOauth'),
        ];
    }

    public function save(OsmSettingsStore $osmSettings): void
    {
        $state = $this->form->getState();

        foreach (ScoutSection::ageOrderedCases() as $section) {
            $fieldName = $this->sectionMappingFieldName($section);

            if (array_key_exists($fieldName, $state)) {
                $osmSettings->{$fieldName} = (string) ($state[$fieldName] ?? '');
            }
        }

        $osmSettings->target_section_id = $state['target_section_id'];
        $osmSettings->target_term_id = (string) ($state['target_term_id'] ?? '');
        $osmSettings->save();

        Notification::make()
            ->title('OSM mappings saved')
            ->success()
            ->send();
    }

    public function refreshDirectoryData(): void
    {
        $osmSettings = app(OsmSettingsStore::class);

        if (! $this->oauthConfigured) {
            Notification::make()
                ->title('Add the OSM environment credentials before queueing a refresh.')
                ->warning()
                ->send();

            return;
        }

        if (! $osmSettings->hasOauthTokens()) {
            Notification::make()
                ->title('Connect OSM before queueing a refresh.')
                ->warning()
                ->send();

            return;
        }

        $osmSettings->markDirectoryRefreshQueued();
        RefreshOsmDirectorySnapshot::dispatch();
        $this->hydrateDirectoryData($osmSettings);

        Notification::make()
            ->title('OSM data refresh queued')
            ->success()
            ->send();
    }

    public function seedLeaders(OsmSettingsStore $osmSettings): void
    {
        if (! $this->hasMappedPublicSections($osmSettings)) {
            Notification::make()
                ->title('Map at least one public section before importing leaders.')
                ->warning()
                ->send();

            return;
        }

        ImportOsmSectionLeaders::dispatch();

        Notification::make()
            ->title('OSM leader import queued')
            ->body('The mapped OSM sections will be checked in the background.')
            ->success()
            ->send();
    }

    public function disconnectOauth(OsmAuthenticatorManager $osmAuthenticatorManager): void
    {
        $osmAuthenticatorManager->disconnect();
        app(OsmSettingsStore::class)->clearDirectorySnapshot();

        $this->connected = false;
        $this->connectedAccount = null;
        $this->directoryError = null;
        $this->directoryRefreshedAt = null;
        $this->directoryRefreshQueuedAt = null;

        Notification::make()
            ->title('OSM connection removed')
            ->success()
            ->send();
    }

    /**
     * @return array<string, string>
     */
    private function sectionOptions(): array
    {
        return app(OsmSettingsStore::class)->directorySections();
    }

    /**
     * @return array<string, string>
     */
    private function termOptionsFor(string $sectionId): array
    {
        return app(OsmSettingsStore::class)->directoryTermsBySection()[$sectionId] ?? [];
    }

    private function sectionHasTerms(string $sectionId): bool
    {
        return $this->termOptionsFor($sectionId) !== [];
    }

    private function termHelperTextFor(string $sectionId): ?string
    {
        if (blank($sectionId)) {
            return null;
        }

        if ($this->sectionHasTerms($sectionId)) {
            return null;
        }

        return 'OSM has not returned any terms for this section, so the term can be left blank.';
    }

    private function sectionMappingSelect(string $name, string $label): Select
    {
        return Select::make($name)
            ->label($label)
            ->options(fn (): array => $this->sectionOptions())
            ->searchable()
            ->preload()
            ->disabled(fn (): bool => ! $this->connected || $this->sectionOptions() === []);
    }

    /**
     * @return array<string, string>
     */
    private function publicSectionMappingState(OsmSettingsStore $osmSettings): array
    {
        return collect(ScoutSection::ageOrderedCases())
            ->mapWithKeys(fn (ScoutSection $section): array => [
                $this->sectionMappingFieldName($section) => $osmSettings->{$this->sectionMappingFieldName($section)},
            ])
            ->all();
    }

    /**
     * @return list<Select>
     */
    private function publicSectionMappingFields(): array
    {
        $sectionSettings = app(SectionSettingsStore::class);

        return collect(ScoutSection::ageOrderedCases())
            ->filter(fn (ScoutSection $section): bool => $sectionSettings->isEnabled($section))
            ->map(fn (ScoutSection $section): Select => $this->sectionMappingSelect(
                $this->sectionMappingFieldName($section),
                $section->value.' OSM section',
            ))
            ->values()
            ->all();
    }

    private function sectionMappingFieldName(ScoutSection $section): string
    {
        return $section->slug().'_section_id';
    }

    private function hasMappedPublicSections(OsmSettingsStore $osmSettings): bool
    {
        return collect($osmSettings->publicSectionMappings())
            ->contains(fn (string $sectionId): bool => filled($sectionId));
    }

    private function hydrateDirectoryData(OsmSettingsStore $osmSettings): void
    {
        if (! $this->oauthConfigured) {
            $this->connected = false;
            $this->connectedAccount = null;
            $this->directoryError = 'Set OSM client credentials in the environment before connecting this site.';
            $this->directoryRefreshedAt = null;
            $this->directoryRefreshQueuedAt = null;

            return;
        }

        $this->connected = $osmSettings->hasOauthTokens();
        $this->connectedAccount = collect([
            $osmSettings->directory_account_name,
            $osmSettings->directory_account_email,
        ])->filter()->join(' - ');
        $this->directoryRefreshedAt = $osmSettings->directory_refreshed_at;
        $this->directoryRefreshQueuedAt = $osmSettings->directory_refresh_queued_at;
        $this->directoryError = $osmSettings->directory_last_error;

        if (! $osmSettings->hasOauthTokens()) {
            $this->directoryError = 'Connect OSM to load the available sections and terms.';
        } elseif ($this->sectionOptions() === [] && blank($this->directoryError)) {
            $this->directoryError = 'Queue an OSM refresh to load the available sections and terms.';
        }
    }

    private function dispatchMountNotification(): void
    {
        if (filled(session('osm_oauth_status'))) {
            Notification::make()
                ->title((string) session('osm_oauth_status'))
                ->success()
                ->send();
        }

        if (filled(session('osm_oauth_error'))) {
            Notification::make()
                ->title((string) session('osm_oauth_error'))
                ->danger()
                ->send();
        }
    }
}
