<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Brick\Money\Money;
use Database\Factories\MealFoodItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class MealFoodItem extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<MealFoodItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'meal_id',
        'food_item_id',
        'food_price_id',
        'amount_per_serving',
        'price_per_pack',
        'servings_per_pack',
        'calories_per_pack',
        'priced_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_per_serving' => 'decimal:2',
            'price_per_pack' => MoneyCast::class.':GBP',
            'servings_per_pack' => 'integer',
            'calories_per_pack' => 'integer',
            'priced_at' => 'date',
        ];
    }

    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class);
    }

    public function foodItem(): BelongsTo
    {
        return $this->belongsTo(FoodItem::class);
    }

    public function foodPrice(): BelongsTo
    {
        return $this->belongsTo(FoodPrice::class);
    }

    public function packsRequired(int $peopleCount): int
    {
        return (int) ceil(($peopleCount * (float) $this->amount_per_serving) / $this->servings_per_pack);
    }

    public function costPerServingMinor(): int
    {
        return (int) round($this->price_per_pack->getMinorAmount()->toInt() * ((float) $this->amount_per_serving / $this->servings_per_pack));
    }

    public function totalCostMinor(int $peopleCount): int
    {
        return $this->packsRequired($peopleCount) * $this->price_per_pack->getMinorAmount()->toInt();
    }

    public function caloriesPerServing(): int
    {
        return (int) round($this->calories_per_pack * ((float) $this->amount_per_serving / $this->servings_per_pack));
    }

    public function isStale(): bool
    {
        $latestPrice = $this->foodItem?->latestPrice;

        if (! $latestPrice instanceof FoodPrice) {
            return false;
        }

        return $this->food_price_id !== $latestPrice->id
            || ! $this->price_per_pack->isEqualTo($latestPrice->price_per_pack);
    }

    public function refreshPriceSnapshot(FoodPrice $foodPrice): void
    {
        $foodItem = $foodPrice->foodItem;

        $this->forceFill([
            'food_price_id' => $foodPrice->id,
            'price_per_pack' => Money::ofMinor($foodPrice->price_per_pack->getMinorAmount()->toInt(), 'GBP'),
            'servings_per_pack' => $foodItem->servings_per_pack,
            'calories_per_pack' => $foodItem->calories_per_pack,
            'priced_at' => $foodPrice->priced_at,
        ])->save();
    }
}
