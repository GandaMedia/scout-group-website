<?php

use App\Enums\Section;
use App\Models\CalendarEvent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

it('stores section enums as their backed values', function () {
    $event = CalendarEvent::query()->create([
        'title' => 'District camp',
        'starts_at' => now(),
        'ends_at' => now()->addHour()->toDateTimeString(),
        'content' => 'Details',
        'all_day' => false,
        'sections' => [Section::SCOUTS, Section::CUBS],
    ]);

    expect($event->sections)->toBe([Section::SCOUTS, Section::CUBS])
        ->and(json_decode($event->getRawOriginal('sections'), true))->toBe([
            Section::SCOUTS->value,
            Section::CUBS->value,
        ]);
});

it('hydrates sections as an array of section enums', function () {
    $eventId = DB::table('calendar_events')->insertGetId([
        'title' => 'Pack night',
        'slug' => 'pack-night',
        'starts_at' => now(),
        'ends_at' => now()->addHour()->toDateTimeString(),
        'content' => 'Activities',
        'all_day' => false,
        'sections' => json_encode([
            Section::BEAVERS->value,
            Section::SQUIRRELS->value,
        ], JSON_THROW_ON_ERROR),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $event = CalendarEvent::query()->findOrFail($eventId);

    expect($event->sections)->toBeArray()
        ->and($event->sections)->toBe([Section::BEAVERS, Section::SQUIRRELS])
        ->and($event->sections[0])->toBeInstanceOf(Section::class)
        ->and($event->sections[1])->toBeInstanceOf(Section::class);
});

it('includes calendar metadata when converted to a guava calendar event', function () {
    $event = CalendarEvent::query()->create([
        'title' => 'District camp',
        'starts_at' => now(),
        'ends_at' => now()->addHour()->toDateTimeString(),
        'content' => 'Bring lunch',
        'all_day' => true,
        'sections' => [Section::SCOUTS, Section::CUBS],
    ]);

    $calendarEvent = $event->toCalendarEvent();
    $calendarObject = $calendarEvent->toCalendarObject(0, false);

    expect($calendarEvent->getTitle())->toBe('District camp')
        ->and($calendarEvent->getAllDay())->toBeTrue()
        ->and($calendarEvent->getExtendedProps())->toBe([
            'key' => $event->getRouteKey(),
            'model' => CalendarEvent::class,
            'content' => 'Bring lunch',
            'is_manual' => true,
            'sections' => [Section::SCOUTS->value, Section::CUBS->value],
        ])
        ->and($calendarObject['extendedProps'])->not->toHaveKey('url')
        ->and($calendarObject['extendedProps'])->not->toHaveKey('url_target');
});

it('registers image conversions for calendar event media', function () {
    Storage::fake('public');

    $event = CalendarEvent::factory()->create([
        'title' => 'Summer camp',
    ]);

    $event
        ->addMedia(UploadedFile::fake()->image('poster.jpg', 1600, 1200))
        ->toMediaCollection('image');

    $media = $event->refresh()->getFirstMedia('image');

    expect($media)->not->toBeNull()
        ->and($media->hasGeneratedConversion('large'))->toBeTrue()
        ->and($media->hasGeneratedConversion('medium'))->toBeTrue()
        ->and($media->hasGeneratedConversion('thumb'))->toBeTrue();

    [$largeWidth, $largeHeight] = getimagesize($media->getPath('large'));
    [$mediumWidth, $mediumHeight] = getimagesize($media->getPath('medium'));
    [$thumbWidth, $thumbHeight] = getimagesize($media->getPath('thumb'));

    expect([$largeWidth, $largeHeight])->toBe([1000, 750])
        ->and([$mediumWidth, $mediumHeight])->toBe([250, 188])
        ->and([$thumbWidth, $thumbHeight])->toBe([100, 75]);
});
