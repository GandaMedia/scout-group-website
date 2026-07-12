<?php

namespace App\Models;

use App\Casts\CalendarEventSectionsCast;
use App\Enums\Section;
use Database\Factories\CalendarEventFactory;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent as GuavaCalendarEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class CalendarEvent extends Model implements AuditableContract, Eventable, HasMedia
{
    use Auditable;

    /** @use HasFactory<CalendarEventFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    use HasSlug;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'starts_at',
        'ends_at',
        'content',
        'all_day',
        'is_manual',
        'sync_merge_key',
        'sections',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_manual' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
            'is_manual' => 'boolean',
            'sections' => CalendarEventSectionsCast::class,
        ];
    }

    public function feedEventLinks(): HasMany
    {
        return $this->hasMany(CalendarFeedEventLink::class);
    }

    public function isSynced(): bool
    {
        return ! $this->is_manual;
    }

    public function toCalendarEvent(): GuavaCalendarEvent
    {
        // For eloquent models, make sure to pass the model to the constructor
        return GuavaCalendarEvent::make($this)
            ->title($this->title)
            ->key($this->getRouteKey())
            ->start($this->starts_at)
            ->end($this->ends_at)
            ->allDay($this->all_day)
            ->extendedProps([
                'content' => $this->content,
                'is_manual' => $this->is_manual,
                'sections' => array_map(
                    static fn (Section $section): string => $section->value,
                    $this->sections,
                ),
            ]);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('large')
            ->fit(Fit::Max, 1000, 1000)
            ->performOnCollections('image')
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->fit(Fit::Max, 250, 250)
            ->performOnCollections('image')
            ->nonQueued();

        $this->addMediaConversion('thumb')
            ->fit(Fit::Max, 100, 100)
            ->performOnCollections('image')
            ->nonQueued();
    }
}
