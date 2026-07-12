<?php

use App\Filament\Resources\ContactEnquiries\Pages\ListContactEnquiries;
use App\Filament\Resources\ContactEnquiries\Pages\ViewContactEnquiry;
use App\Models\ContactEnquiry;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists enquiries and marks them as reviewed from the table', function () {
    $pendingEnquiry = ContactEnquiry::factory()->create([
        'name' => 'Pending Parent',
    ]);

    $reviewedEnquiry = ContactEnquiry::factory()->reviewed()->create([
        'name' => 'Reviewed Parent',
    ]);

    Livewire::test(ListContactEnquiries::class)
        ->assertSee($pendingEnquiry->name)
        ->assertSee($reviewedEnquiry->name)
        ->assertActionExists(TestAction::make('markReviewed')->table($pendingEnquiry))
        ->callAction(TestAction::make('markReviewed')->table($pendingEnquiry));

    expect($pendingEnquiry->fresh()->reviewed_at)->not->toBeNull();
});

it('loads the enquiry view page', function () {
    $contactEnquiry = ContactEnquiry::factory()->create([
        'name' => 'View Parent',
        'message' => 'Please tell me more about Cubs.',
    ]);

    Livewire::test(ViewContactEnquiry::class, ['record' => $contactEnquiry->getRouteKey()])
        ->assertOk()
        ->assertSee($contactEnquiry->name)
        ->assertSee($contactEnquiry->message);
});
