<?php

use App\Enums\Section;
use App\Filament\Blocks\SectionLeadersBlock;
use App\Filament\Resources\Leaders\Pages\CreateLeader;
use App\Filament\Resources\LeaderSections\Pages\CreateLeaderSection;
use App\Filament\Resources\LeaderSections\Pages\ListLeaderSections;
use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Models\Leader;
use App\Models\LeaderSection;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('registers the section leaders page builder block', function () {
    expect(PageForm::blocks())->toContain(SectionLeadersBlock::class);
});

it('uses leader profile, photo, and section assignment fields', function () {
    Livewire::test(CreateLeader::class)
        ->assertFormFieldExists('photo', function (SpatieMediaLibraryFileUpload $field): bool {
            expect($field->getCollection())->toBe('photo')
                ->and($field->isMultiple())->toBeFalse();

            return true;
        })
        ->assertFormFieldExists('sectionAssignments', function (Repeater $field): bool {
            expect($field)->toBeInstanceOf(Repeater::class);

            return true;
        });
});

it('creates leaders with a photo and section assignments', function () {
    Storage::fake('public');

    $undoRepeaterFake = Repeater::fake();

    try {
        Livewire::test(CreateLeader::class)
            ->fillForm([
                'name' => 'Alex Example',
                'scout_name' => 'Penguin',
                'bio' => 'I help Scouts make memories.',
                'fun_fact' => 'I like cold-water kayaking.',
                'is_active' => true,
                'sectionAssignments' => [
                    [
                        'section' => Section::SCOUTS->value,
                    ],
                    [
                        'section' => Section::CUBS->value,
                    ],
                ],
            ])
            ->set('data.photo', UploadedFile::fake()->image('leader.jpg', 900, 900))
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();
    } finally {
        $undoRepeaterFake();
    }

    $leader = Leader::query()->where('name', 'Alex Example')->with('sectionAssignments')->sole();

    expect($leader->getFirstMedia('photo'))->not->toBeNull()
        ->and($leader->sectionAssignments)->toHaveCount(2)
        ->and($leader->sectionAssignments->pluck('section')->all())->toEqualCanonicalizing([
            Section::SCOUTS,
            Section::CUBS,
        ]);
});

it('requires leader name bio and photo', function () {
    Livewire::test(CreateLeader::class)
        ->fillForm([
            'name' => null,
            'bio' => null,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'bio' => 'required',
            'photo' => 'required',
        ]);
});

it('uses a section assignment resource for per-section ordering', function () {
    $leader = Leader::factory()->create();

    Livewire::test(CreateLeaderSection::class)
        ->assertFormFieldExists('leader_id')
        ->assertFormFieldExists('section', function (Select $field): bool {
            expect($field->getOptions())->toBe([
                Section::SCOUTS->value => Section::SCOUTS->getLabel(),
                Section::CUBS->value => Section::CUBS->getLabel(),
                Section::BEAVERS->value => Section::BEAVERS->getLabel(),
                Section::SQUIRRELS->value => Section::SQUIRRELS->getLabel(),
                Section::EXPLORERS->value => Section::EXPLORERS->getLabel(),
                Section::NETWORK->value => Section::NETWORK->getLabel(),
            ]);

            return true;
        })
        ->fillForm([
            'leader_id' => $leader->id,
            'section' => Section::BEAVERS->value,
            'order_column' => 7,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    expect(LeaderSection::query()->whereBelongsTo($leader)->sole()->section)->toBe(Section::BEAVERS);
});

it('shows leader section rows with a thumbnail table', function () {
    $assignment = LeaderSection::factory()->section(Section::SCOUTS)->create();

    Livewire::test(ListLeaderSections::class)
        ->assertTableColumnExists('leader_photo', function (ImageColumn $column): bool {
            expect($column)->toBeInstanceOf(ImageColumn::class);

            return true;
        }, $assignment)
        ->assertTableColumnExists('leader.name', record: $assignment)
        ->assertTableColumnExists('section', record: $assignment);
});
