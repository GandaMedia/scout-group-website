<?php

use App\Enums\WaitingListEntrySyncStatus;
use App\Filament\Resources\WaitingListEntries\Pages\ListWaitingListEntries;
use App\Filament\Resources\WaitingListEntries\Pages\ViewWaitingListEntry;
use App\Jobs\SyncWaitingListEntry;
use App\Models\User;
use App\Models\WaitingListEntry;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists entries, filters by sync status, and exposes the manual actions', function () {
    $pendingEntry = WaitingListEntry::factory()->create([
        'first_name' => 'Pending',
        'last_name' => 'Child',
    ]);
    $duplicateEntry = WaitingListEntry::factory()->heldDuplicate()->create([
        'first_name' => 'Duplicate',
        'last_name' => 'Child',
    ]);
    $failedEntry = WaitingListEntry::factory()->failed()->create([
        'first_name' => 'Failed',
        'last_name' => 'Child',
    ]);

    Livewire::test(ListWaitingListEntries::class)
        ->assertCanSeeTableRecords([$pendingEntry, $duplicateEntry, $failedEntry])
        ->assertActionExists(TestAction::make('releaseDuplicate')->table($duplicateEntry))
        ->assertActionExists(TestAction::make('retrySync')->table($failedEntry))
        ->filterTable('sync_status', WaitingListEntrySyncStatus::FAILED->value)
        ->assertCanSeeTableRecords([$failedEntry])
        ->assertCanNotSeeTableRecords([$pendingEntry, $duplicateEntry]);
});

it('releases duplicate-held records for sync from the table action', function () {
    $duplicateEntry = WaitingListEntry::factory()->heldDuplicate()->create();

    Livewire::test(ListWaitingListEntries::class)
        ->callAction(TestAction::make('releaseDuplicate')->table($duplicateEntry));

    $duplicateEntry->refresh();

    expect($duplicateEntry->sync_status)->toBe(WaitingListEntrySyncStatus::PENDING)
        ->and($duplicateEntry->is_possible_duplicate)->toBeFalse()
        ->and($duplicateEntry->duplicate_reason)->toBeNull()
        ->and($duplicateEntry->sync_queued_at)->not->toBeNull();
});

it('queues a retry from the table action for failed records', function () {
    Queue::fake();

    $failedEntry = WaitingListEntry::factory()->failed()->create();

    Livewire::test(ListWaitingListEntries::class)
        ->callAction(TestAction::make('retrySync')->table($failedEntry));

    $failedEntry->refresh();

    expect($failedEntry->sync_status)->toBe(WaitingListEntrySyncStatus::PENDING)
        ->and($failedEntry->last_error)->toBeNull();

    Queue::assertPushed(SyncWaitingListEntry::class, function (SyncWaitingListEntry $job) use ($failedEntry): bool {
        return $job->waitingListEntryId === $failedEntry->id;
    });
});

it('loads the waiting-list entry view page', function () {
    $entry = WaitingListEntry::factory()->create([
        'first_name' => 'View',
        'last_name' => 'Child',
        'parent_name' => 'View Parent',
        'notes' => 'Needs Wednesday sessions if possible.',
    ]);

    Livewire::test(ViewWaitingListEntry::class, ['record' => $entry->getRouteKey()])
        ->assertOk()
        ->assertSee('View Child')
        ->assertSee('View Parent')
        ->assertSee('Needs Wednesday sessions if possible.');
});
