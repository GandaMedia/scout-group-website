<?php

namespace App\Models;

use App\Enums\CalendarFeedSyncStatus;
use App\Enums\Section;
use Database\Factories\CalendarFeedSourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CalendarFeedSource extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<CalendarFeedSourceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'section',
        'feed_url',
        'is_enabled',
        'last_synced_at',
        'last_sync_status',
        'last_sync_error',
        'last_event_count',
        'etag',
        'last_modified',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section' => Section::class,
            'is_enabled' => 'boolean',
            'last_synced_at' => 'datetime',
            'last_sync_status' => CalendarFeedSyncStatus::class,
            'last_event_count' => 'integer',
        ];
    }

    public function feedEventLinks(): HasMany
    {
        return $this->hasMany(CalendarFeedEventLink::class);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }
}
