<?php

namespace App\Models;

use Database\Factories\LeaderFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Leader extends Model implements AuditableContract, HasMedia
{
    use Auditable;

    /** @use HasFactory<LeaderFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'scout_name',
        'bio',
        'fun_fact',
        'is_active',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function sectionAssignments(): HasMany
    {
        return $this->hasMany(LeaderSection::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 720, 720)
            ->performOnCollections('photo')
            ->nonQueued();

        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 120, 120)
            ->performOnCollections('photo')
            ->nonQueued();
    }

    public function photoUrl(string $conversion = 'card'): ?string
    {
        if (! $this->hasMedia('photo')) {
            return null;
        }

        return $this->getFirstMediaUrl('photo', $conversion);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
