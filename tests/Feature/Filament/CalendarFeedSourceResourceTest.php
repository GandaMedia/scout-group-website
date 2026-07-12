<?php

use App\Enums\CalendarFeedSyncStatus;
use App\Enums\Section;
use App\Filament\Resources\CalendarEvents\Pages\EditCalendarEvent;
use App\Filament\Resources\CalendarFeedSources\Pages\CreateCalendarFeedSource;
use App\Filament\Resources\CalendarFeedSources\Pages\EditCalendarFeedSource;
use App\Filament\Resources\CalendarFeedSources\Pages\ListCalendarFeedSources;
use App\Models\CalendarEvent;
use App\Models\CalendarFeedSource;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('uses a section select, URL field, and enabled toggle on the create form', function () {
    Livewire::test(CreateCalendarFeedSource::class)
        ->assertFormFieldExists('section', function (Select $field): bool {
            expect($field)->toBeInstanceOf(Select::class)
                ->and($field->getOptions())->toBe([
                    Section::SCOUTS->value => Section::SCOUTS->getLabel(),
                    Section::CUBS->value => Section::CUBS->getLabel(),
                    Section::BEAVERS->value => Section::BEAVERS->getLabel(),
                    Section::SQUIRRELS->value => Section::SQUIRRELS->getLabel(),
                    Section::EXPLORERS->value => Section::EXPLORERS->getLabel(),
                    Section::NETWORK->value => Section::NETWORK->getLabel(),
                ]);

            return true;
        })
        ->assertFormFieldExists('feed_url', function (TextInput $field): bool {
            expect($field)->toBeInstanceOf(TextInput::class);

            return true;
        })
        ->assertFormFieldExists('is_enabled', function (Toggle $field): bool {
            expect($field)->toBeInstanceOf(Toggle::class);

            return true;
        });
});

it('enforces one feed per section', function () {
    CalendarFeedSource::factory()->create([
        'section' => Section::SCOUTS,
    ]);

    Livewire::test(CreateCalendarFeedSource::class)
        ->fillForm([
            'section' => Section::SCOUTS,
            'feed_url' => 'https://example.test/another-scouts.ics',
            'is_enabled' => true,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'section' => 'unique',
        ]);
});

it('creates feed sources for sections', function () {
    Livewire::test(CreateCalendarFeedSource::class)
        ->fillForm([
            'section' => Section::BEAVERS,
            'feed_url' => 'https://example.test/beavers.ics',
            'is_enabled' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $source = CalendarFeedSource::query()->sole();

    expect($source->section)->toBe(Section::BEAVERS)
        ->and($source->feed_url)->toBe('https://example.test/beavers.ics')
        ->and($source->last_sync_status)->toBe(CalendarFeedSyncStatus::NEVER);
});

it('shows sync actions and status columns on the list page', function () {
    $source = CalendarFeedSource::factory()->create();

    Livewire::test(ListCalendarFeedSources::class)
        ->assertActionExists('syncAll')
        ->assertActionExists(TestAction::make('syncNow')->table($source))
        ->assertTableColumnExists('last_sync_status', function (TextColumn $column): bool {
            expect($column)->toBeInstanceOf(TextColumn::class);

            return true;
        }, $source);
});

it('loads the edit page for a feed source', function () {
    $source = CalendarFeedSource::factory()->create([
        'section' => Section::CUBS,
    ]);

    Livewire::test(EditCalendarFeedSource::class, ['record' => $source->getRouteKey()])
        ->assertOk()
        ->assertSee('Cubs');
});

it('syncs a single feed from the admin list action', function () {
    $source = CalendarFeedSource::factory()->create([
        'section' => Section::SCOUTS,
        'feed_url' => 'https://example.test/scouts.ics',
    ]);

    Http::fake([
        'https://example.test/scouts.ics' => Http::response(feedSourceIcsFeed([
            feedSourceIcsEvent(
                uid: 'row-action-event',
                title: 'Row Action Event',
                startsAt: '20260812T190000',
                endsAt: '20260812T203000',
            ),
        ]), 200),
    ]);

    Livewire::test(ListCalendarFeedSources::class)
        ->callAction(TestAction::make('syncNow')->table($source));

    expect(CalendarEvent::query()->where('title', 'Row Action Event')->exists())->toBeTrue();
});

it('syncs all enabled feeds from the list page header action', function () {
    CalendarFeedSource::factory()->create([
        'section' => Section::SCOUTS,
        'feed_url' => 'https://example.test/scouts.ics',
    ]);

    CalendarFeedSource::factory()->create([
        'section' => Section::CUBS,
        'feed_url' => 'https://example.test/cubs.ics',
        'is_enabled' => false,
    ]);

    Http::fake([
        'https://example.test/scouts.ics' => Http::response(feedSourceIcsFeed([
            feedSourceIcsEvent(
                uid: 'sync-all-event',
                title: 'Sync All Event',
                startsAt: '20260815T190000',
                endsAt: '20260815T203000',
            ),
        ]), 200),
    ]);

    Livewire::test(ListCalendarFeedSources::class)
        ->callAction('syncAll');

    expect(CalendarEvent::query()->where('title', 'Sync All Event')->exists())->toBeTrue();
});

it('shows synced calendar events as read only', function () {
    $event = CalendarEvent::factory()->create([
        'is_manual' => false,
        'sync_merge_key' => 'feed-owned-event',
    ]);

    Livewire::test(EditCalendarEvent::class, ['record' => $event->getRouteKey()])
        ->assertActionExists('syncedNotice')
        ->assertFormFieldExists('title', fn (TextInput $field): bool => $field->isDisabled());
});

function feedSourceIcsFeed(array $events): string
{
    return implode("\r\n", [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Example Scout Group//Calendar//EN',
        ...$events,
        'END:VCALENDAR',
        '',
    ]);
}

function feedSourceIcsEvent(
    string $uid,
    string $title,
    string $startsAt,
    string $endsAt,
    ?string $description = null,
): string {
    $lines = [
        'BEGIN:VEVENT',
        'UID:'.$uid,
        'SUMMARY:'.$title,
        'DTSTART;TZID=Europe/London:'.$startsAt,
        'DTEND;TZID=Europe/London:'.$endsAt,
    ];

    if ($description !== null) {
        $lines[] = 'DESCRIPTION:'.$description;
    }

    $lines[] = 'END:VEVENT';

    return implode("\r\n", $lines);
}
