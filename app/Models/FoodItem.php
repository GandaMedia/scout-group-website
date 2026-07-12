<?php

namespace App\Models;

use Database\Factories\FoodItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class FoodItem extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<FoodItemFactory> */
    use HasFactory;

    use Searchable;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'brand_id',
        'store_id',
        'search_brand',
        'search_store',
        'servings_per_pack',
        'calories_per_pack',
        'created_by_user_id',
    ];

    protected static function booted(): void
    {
        self::saving(static function (FoodItem $foodItem): void {
            $foodItem->search_brand = Brand::query()->whereKey($foodItem->brand_id)->value('name');
            $foodItem->search_store = Store::query()->whereKey($foodItem->store_id)->value('name');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'servings_per_pack' => 'integer',
            'calories_per_pack' => 'integer',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(FoodPrice::class);
    }

    public function latestPrice(): HasOne
    {
        return $this->hasOne(FoodPrice::class)->latestOfMany('priced_at');
    }

    public function mealFoodItems(): HasMany
    {
        return $this->hasMany(MealFoodItem::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['brand', 'store']);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'search_brand' => $this->search_brand,
            'search_store' => $this->search_store,
        ];
    }
}
