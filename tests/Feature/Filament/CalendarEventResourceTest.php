<?php

use App\Enums\Section;
use App\Filament\Resources\CalendarEvents\Pages\CreateCalendarEvent;
use App\Filament\Resources\CalendarEvents\Pages\ListCalendarEvents;
use App\Models\CalendarEvent;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('uses a multi select for sections on the create form', function () {
    Livewire::test(CreateCalendarEvent::class)
        ->assertFormFieldExists('sections', function (Select $field): bool {
            expect($field)->toBeInstanceOf(Select::class)
                ->and($field->isMultiple())->toBeTrue()
                ->and($field->getOptions())->toBe([
                    Section::SCOUTS->value => Section::SCOUTS->getLabel(),
                    Section::CUBS->value => Section::CUBS->getLabel(),
                    Section::BEAVERS->value => Section::BEAVERS->getLabel(),
                    Section::SQUIRRELS->value => Section::SQUIRRELS->getLabel(),
                    Section::EXPLORERS->value => Section::EXPLORERS->getLabel(),
                    Section::NETWORK->value => Section::NETWORK->getLabel(),
                ]);

            return true;
        });
});

it('uses a slug field and a single image upload on the create form', function () {
    Livewire::test(CreateCalendarEvent::class)
        ->assertFormFieldExists('slug', function (TextInput $field): bool {
            expect($field)->toBeInstanceOf(TextInput::class);

            return true;
        })
        ->assertFormFieldExists('image', function (SpatieMediaLibraryFileUpload $field): bool {
            expect($field)->toBeInstanceOf(SpatieMediaLibraryFileUpload::class)
                ->and($field->getCollection())->toBe('image')
                ->and($field->isMultiple())->toBeFalse();

            return true;
        });
});

it('uses grouped toggle buttons for all day events and switches the pickers to date-only mode', function () {
    Livewire::test(CreateCalendarEvent::class)
        ->assertFormFieldExists('all_day', function (ToggleButtons $field): bool {
            expect($field)->toBeInstanceOf(ToggleButtons::class);

            return true;
        })
        ->fillForm([
            'all_day' => 1,
        ])
        ->assertFormFieldExists('starts_at', function (DateTimePicker $field): bool {
            expect($field->isNative())->toBeTrue()
                ->and($field->hasTime())->toBeFalse()
                ->and($field->getType())->toBe('date');

            return true;
        })
        ->assertFormFieldExists('ends_at', function (DateTimePicker $field): bool {
            expect($field->isNative())->toBeTrue()
                ->and($field->hasTime())->toBeFalse()
                ->and($field->getType())->toBe('date');

            return true;
        });
});

it('live updates the reciprocal date constraints between the start and end fields', function () {
    $startsAt = now()->addDay()->format('Y-m-d H:i');
    $endsAt = now()->addDays(2)->format('Y-m-d H:i');

    Livewire::test(CreateCalendarEvent::class)
        ->fillForm([
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'all_day' => 0,
        ])
        ->assertFormFieldExists('starts_at', function (DateTimePicker $field) use ($endsAt): bool {
            expect($field->getMaxDate())->toBe($endsAt);

            return true;
        })
        ->assertFormFieldExists('ends_at', function (DateTimePicker $field) use ($startsAt): bool {
            expect($field->getMinDate())->toBe($startsAt);

            return true;
        });
});

it('creates calendar events with multiple selected sections', function () {
    Storage::fake('public');

    $image = UploadedFile::fake()->image('poster.jpg', 1600, 1200);

    Livewire::test(CreateCalendarEvent::class)
        ->fillForm([
            'title' => 'Camp planning',
            'slug' => 'camp-planning',
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addHour()->toDateTimeString(),
            'content' => 'Agenda',
            'all_day' => 0,
            'sections' => [Section::SCOUTS, Section::CUBS],
        ])
        ->set('data.image', $image)
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $event = CalendarEvent::query()->latest('id')->first();

    expect($event)->not->toBeNull()
        ->and($event->slug)->toBe('camp-planning')
        ->and($event->sections)->toBe([Section::SCOUTS, Section::CUBS])
        ->and($event->getFirstMedia('image'))->not->toBeNull();
});

it('requires the end date to be after the start date', function () {
    Livewire::test(CreateCalendarEvent::class)
        ->fillForm([
            'title' => 'Camp planning',
            'starts_at' => now()->addDay()->toDateString(),
            'ends_at' => now()->toDateString(),
            'content' => 'Agenda',
            'all_day' => 1,
            'sections' => [Section::SCOUTS],
        ])
        ->call('create')
        ->assertHasFormErrors([
            'ends_at' => 'after',
        ]);
});

it('shows the image thumbnail column on the list table', function () {
    $event = CalendarEvent::factory()->create();

    Livewire::test(ListCalendarEvents::class)
        ->assertTableColumnExists('image', function (SpatieMediaLibraryImageColumn $column): bool {
            expect($column)->toBeInstanceOf(SpatieMediaLibraryImageColumn::class)
                ->and($column->getCollection())->toBe('image')
                ->and($column->getConversion())->toBe('thumb');

            return true;
        }, $event);
});
