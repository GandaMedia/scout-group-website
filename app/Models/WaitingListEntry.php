<?php

namespace App\Models;

use App\Enums\Section;
use App\Enums\WaitingListEntrySyncStatus;
use Database\Factories\WaitingListEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class WaitingListEntry extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<WaitingListEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'section_slug',
        'parent_name',
        'parent_email',
        'parent_phone',
        'postcode',
        'notes',
        'is_possible_duplicate',
        'duplicate_reason',
        'duplicate_detected_at',
        'sync_status',
        'sync_attempts',
        'submitted_at',
        'sync_queued_at',
        'sync_attempted_at',
        'synced_at',
        'osm_scout_id',
        'last_payload',
        'osm_response',
        'last_error',
        'last_error_at',
    ];

    protected $attributes = [
        'is_possible_duplicate' => false,
        'sync_status' => WaitingListEntrySyncStatus::PENDING,
        'sync_attempts' => 0,
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_possible_duplicate' => 'boolean',
            'duplicate_detected_at' => 'datetime',
            'sync_status' => WaitingListEntrySyncStatus::class,
            'submitted_at' => 'datetime',
            'sync_queued_at' => 'datetime',
            'sync_attempted_at' => 'datetime',
            'synced_at' => 'datetime',
            'last_payload' => 'array',
            'osm_response' => 'array',
            'last_error_at' => 'datetime',
        ];
    }

    public function scopeEligibleForSync(Builder $query): Builder
    {
        return $query->where('sync_status', WaitingListEntrySyncStatus::PENDING);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getFullNameAttribute(): string
    {
        return $this->fullName();
    }

    public function getSectionLabelAttribute(): string
    {
        return $this->section()?->value ?? $this->section_slug;
    }

    public function section(): ?Section
    {
        return Section::fromSlug($this->section_slug);
    }

    public function queueForSync(): void
    {
        $this->forceFill([
            'sync_status' => WaitingListEntrySyncStatus::PENDING,
            'sync_queued_at' => now(),
            'last_error' => null,
            'last_error_at' => null,
        ])->save();
    }

    public function releaseDuplicateHold(): void
    {
        $this->forceFill([
            'is_possible_duplicate' => false,
            'duplicate_reason' => null,
            'duplicate_detected_at' => null,
            'sync_status' => WaitingListEntrySyncStatus::PENDING,
            'last_error' => null,
            'last_error_at' => null,
            'sync_queued_at' => now(),
        ])->save();
    }

    /**
     * @param  array<string, string>  $payload
     */
    public function markSyncing(array $payload): void
    {
        $this->forceFill([
            'sync_status' => WaitingListEntrySyncStatus::SYNCING,
            'sync_attempts' => $this->sync_attempts + 1,
            'sync_attempted_at' => now(),
            'last_payload' => $payload,
            'last_error' => null,
            'last_error_at' => null,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function markSynced(int $scoutId, array $response): void
    {
        $this->forceFill([
            'sync_status' => WaitingListEntrySyncStatus::SYNCED,
            'synced_at' => now(),
            'osm_scout_id' => $scoutId,
            'osm_response' => $response,
            'last_error' => null,
            'last_error_at' => null,
        ])->save();
    }

    /**
     * @param  array<string, mixed>|null  $response
     */
    public function markFailed(string $message, ?array $response = null): void
    {
        $this->forceFill([
            'sync_status' => WaitingListEntrySyncStatus::FAILED,
            'last_error' => $message,
            'last_error_at' => now(),
            'osm_response' => $response,
        ])->save();
    }

    public function ageLabel(): ?string
    {
        if (! $this->date_of_birth instanceof Carbon) {
            return null;
        }

        $years = $this->date_of_birth->diff(now())->y;
        $months = $this->date_of_birth->diff(now())->m;

        return "{$years}y {$months}m";
    }
}
