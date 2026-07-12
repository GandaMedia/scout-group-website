<?php

namespace App\Filament\Pages;

use App\Enums\PageStatus;
use App\Enums\Section as ScoutSection;
use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Models\Page as PageModel;
use App\Settings\SectionSettings as SectionSettingsStore;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Redberry\PageBuilderPlugin\Components\Forms\PageBuilder;
use UnitEnum;

class SectionSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Settings;

    protected static ?string $navigationLabel = 'Section settings';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.section-settings';

    public ?array $data = [];

    public function mount(SectionSettingsStore $sectionSettings): void
    {
        $this->form->fill([
            'sections' => collect(ScoutSection::cases())
                ->mapWithKeys(function (ScoutSection $section) use ($sectionSettings): array {
                    $page = $this->sectionPage($section);

                    return [
                        $section->slug() => [
                            'enabled' => $sectionSettings->isEnabled($section),
                            'title' => $page->title,
                        ],
                    ];
                })
                ->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Public section availability')
                    ->description('Control which sections appear publicly and edit the content for each system-owned section page.')
                    ->schema([
                        Tabs::make('Sections')
                            ->tabs(
                                collect(ScoutSection::cases())
                                    ->map(fn (ScoutSection $section): Tab => $this->sectionTab($section))
                                    ->all()
                            )
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(SectionSettingsStore $sectionSettings): void
    {
        $state = $this->form->getState();
        $sections = $state['sections'] ?? [];

        $sectionSettings->enabled_section_slugs = collect(ScoutSection::cases())
            ->filter(fn (ScoutSection $section): bool => (bool) data_get($sections, "{$section->slug()}.enabled"))
            ->map(fn (ScoutSection $section): string => $section->slug())
            ->values()
            ->all();
        $sectionSettings->save();

        foreach (ScoutSection::cases() as $section) {
            $page = $this->sectionPage($section);
            $page->title = (string) data_get($sections, "{$section->slug()}.title", $section->value);
            $page->save();
        }

        Cache::forget('mainMenu');

        Notification::make()
            ->title('Section settings saved')
            ->success()
            ->send();
    }

    private function sectionTab(ScoutSection $section): Tab
    {
        return Tab::make($section->value)
            ->schema([
                Toggle::make("sections.{$section->slug()}.enabled")
                    ->label('Publicly available')
                    ->helperText('Disabled sections are hidden from public navigation and return 404 for direct section and waiting-list URLs.'),
                TextInput::make("sections.{$section->slug()}.title")
                    ->label('Page title')
                    ->required()
                    ->maxLength(255),
                TextInput::make("sections.{$section->slug()}.slug")
                    ->label('Fixed page slug')
                    ->default($section->slug())
                    ->disabled()
                    ->dehydrated(false),
                Actions::make([
                    $this->editContentAction($section),
                ])
                    ->key("{$section->slug()}_content_actions")
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private function editContentAction(ScoutSection $section): Action
    {
        return Action::make("edit_{$section->slug()}_content")
            ->label("Edit {$section->value} page content")
            ->icon(Heroicon::PencilSquare)
            ->record(fn (): PageModel => $this->sectionPage($section))
            ->fillForm(fn (): array => [
                'title' => $this->sectionPage($section)->title,
            ])
            ->schema([
                TextInput::make('title')
                    ->label('Page title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Fixed page slug')
                    ->default($section->slug())
                    ->disabled()
                    ->dehydrated(false),
                PageBuilder::make('content')
                    ->blocks(PageForm::blocks())
                    ->selectBlockAction(fn ($action) => $action->selectField(fn ($field) => $field->native()))
                    ->reorderable()
                    ->required()
                    ->columnSpanFull(),
            ])
            ->modalWidth('7xl')
            ->action(function (array $data, Schema $schema) use ($section): void {
                $page = $this->sectionPage($section);
                $page->title = (string) $data['title'];
                $page->save();

                $schema->model($page)->saveRelationships();

                $this->data['sections'][$section->slug()]['title'] = $page->title;

                Cache::forget('mainMenu');
            })
            ->successNotificationTitle("{$section->value} page content saved");
    }

    private function sectionPage(ScoutSection $section): PageModel
    {
        return PageModel::query()->firstOrCreate(
            ['slug' => $section->slug()],
            [
                'title' => $section->value,
                'status' => PageStatus::PUBLISHED,
            ],
        );
    }
}
