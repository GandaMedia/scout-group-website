<?php

namespace App\Models;

use App\Enums\PageStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Redberry\PageBuilderPlugin\Traits\HasPageBuilder;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Page extends Model implements AuditableContract, HasMedia
{
    use Auditable;
    use HasFactory, HasPageBuilder, HasSlug, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'status',
    ];

    protected $attributes = [
        'content' => '',
    ];

    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->doNotGenerateSlugsOnUpdate()
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('pictures');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 100, 100);
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', PageStatus::PUBLISHED);
    }

    public function menuItems(): MorphMany
    {
        return $this->morphMany(MenuItem::class, 'menuable');
    }

    public function getShowUrl(): string
    {
        return route('page.show', $this);
    }
}
