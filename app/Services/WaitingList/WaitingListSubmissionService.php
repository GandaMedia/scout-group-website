<?php

namespace App\Services\WaitingList;

use App\Enums\WaitingListEntrySyncStatus;
use App\Models\WaitingListEntry;
use Illuminate\Support\Str;

class WaitingListSubmissionService
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated): WaitingListEntry
    {
        $firstName = trim((string) $validated['first_name']);
        $lastName = trim((string) $validated['last_name']);
        $sectionSlug = (string) $validated['section_slug'];
        $dateOfBirth = (string) $validated['date_of_birth'];

        $duplicate = WaitingListEntry::query()
            ->whereRaw('lower(first_name) = ?', [Str::lower($firstName)])
            ->whereRaw('lower(last_name) = ?', [Str::lower($lastName)])
            ->whereDate('date_of_birth', $dateOfBirth)
            ->where('section_slug', $sectionSlug)
            ->whereIn('sync_status', array_map(
                static fn (WaitingListEntrySyncStatus $status): string => $status->value,
                WaitingListEntrySyncStatus::cases(),
            ))
            ->exists();

        return WaitingListEntry::query()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'date_of_birth' => $dateOfBirth,
            'section_slug' => $sectionSlug,
            'parent_name' => trim((string) $validated['parent_name']),
            'parent_email' => trim((string) $validated['parent_email']),
            'parent_phone' => trim((string) $validated['parent_phone']),
            'postcode' => Str::upper(trim((string) $validated['postcode'])),
            'notes' => trim((string) ($validated['notes'] ?? '')),
            'is_possible_duplicate' => $duplicate,
            'duplicate_reason' => $duplicate ? 'Potential duplicate waiting-list request for the same child and section.' : null,
            'duplicate_detected_at' => $duplicate ? now() : null,
            'sync_status' => $duplicate ? WaitingListEntrySyncStatus::HELD_DUPLICATE : WaitingListEntrySyncStatus::PENDING,
            'submitted_at' => now(),
            'sync_queued_at' => $duplicate ? null : now(),
        ]);
    }
}
