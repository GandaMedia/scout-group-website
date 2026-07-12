<?php

use App\Casts\MoneyCast;
use App\Models\FoodPrice;
use App\Models\MealFoodItem;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('hydrates food prices from minor units as GBP money', function () {
    $foodPrice = FoodPrice::factory()->create([
        'price_per_pack' => 199,
    ]);

    expect($foodPrice->price_per_pack)->toBeInstanceOf(Money::class)
        ->and($foodPrice->price_per_pack->getMinorAmount()->toInt())->toBe(199)
        ->and($foodPrice->price_per_pack->getCurrency()->getCurrencyCode())->toBe('GBP');
});

it('stores meal line snapshot prices as minor units', function () {
    $line = MealFoodItem::factory()->create([
        'price_per_pack' => Money::ofMinor(85, 'GBP'),
    ]);

    expect($line->getRawOriginal('price_per_pack'))->toBe(85)
        ->and(DB::table('meal_food_items')->where('id', $line->id)->value('price_per_pack'))->toBe(85);
});

it('can persist amount and currency separately', function () {
    Schema::create('money_cast_test_items', function ($table) {
        $table->id();
        $table->integer('price')->nullable();
        $table->string('currency_code', 3)->nullable();
        $table->timestamps();
    });

    $model = new class extends Model
    {
        protected $table = 'money_cast_test_items';

        protected $guarded = [];

        protected function casts(): array
        {
            return [
                'price' => MoneyCast::class.':GBP,currency_code',
            ];
        }
    };

    $item = $model::query()->create([
        'price' => Money::ofMinor(1234, 'EUR'),
    ]);

    $item = $model::query()->findOrFail($item->id);

    expect($item->getRawOriginal('price'))->toBe(1234)
        ->and($item->getRawOriginal('currency_code'))->toBe('EUR')
        ->and($item->price)->toBeInstanceOf(Money::class)
        ->and($item->price->getMinorAmount()->toInt())->toBe(1234)
        ->and($item->price->getCurrency()->getCurrencyCode())->toBe('EUR');
});
