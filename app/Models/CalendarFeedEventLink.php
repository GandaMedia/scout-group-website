<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CalendarFeedEventLink extends Model implements AuditableContract
{
    use Auditable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'calendar_event_id',
        'calendar_feed_source_id',
        'external_event_key',
        'external_event_uid',
        'merge_key',
        'source_fingerprint',
        'payload_hash',
        'last_seen_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    public function feedSource(): BelongsTo
    {
        return $this->belongsTo(CalendarFeedSource::class, 'calendar_feed_source_id');
    }
}
