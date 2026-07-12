<?php

namespace App\Http\Controllers\MealPlanner;

use App\Http\Controllers\Controller;
use App\Http\Requests\MealPlanner\StoreFoodPriceRequest;
use App\Models\FoodItem;
use App\Models\FoodPrice;
use App\Models\MealFoodItem;
use App\Services\MealPlanner\ProjectSnapshotter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class FoodPriceController extends Controller
{
    public function index(FoodItem $foodItem): JsonResponse
    {
        $foodItem->load('prices.createdBy');

        return response()->json([
            'data' => $foodItem->prices()
                ->latest('priced_at')
                ->get()
                ->map(fn ($price): array => [
                    'id' => $price->id,
                    'price_per_pack_minor' => $price->price_per_pack->getMinorAmount()->toInt(),
                    'priced_at' => $price->priced_at->toDateString(),
                    'created_by' => $price->createdBy?->name,
                ]),
        ]);
    }

    public function store(StoreFoodPriceRequest $request, FoodItem $foodItem, ProjectSnapshotter $snapshotter): JsonResponse|RedirectResponse
    {
        $this->authorize('addPrice', $foodItem);

        $mealFoodItem = $this->mealFoodItem($request, $foodItem);

        $validated = $request->validated();
        unset($validated['meal_food_item_id']);

        $foodPrice = DB::transaction(function () use ($foodItem, $mealFoodItem, $request, $snapshotter, $validated): FoodPrice {
            $foodPrice = $foodItem->prices()->create([
                ...$validated,
                'created_by_user_id' => $request->user()->id,
            ]);

            if ($mealFoodItem instanceof MealFoodItem) {
                $mealFoodItem->refreshPriceSnapshot($foodPrice->load('foodItem'));
                $snapshotter->snapshot($mealFoodItem->meal->project, $request->user(), 'meal_line_price_updated');
            }

            return $foodPrice;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $this->pricePayload($foodPrice),
            ], JsonResponse::HTTP_CREATED);
        }

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function pricePayload(FoodPrice $foodPrice): array
    {
        return [
            'id' => $foodPrice->id,
            'price_per_pack_minor' => $foodPrice->price_per_pack->getMinorAmount()->toInt(),
            'priced_at' => $foodPrice->priced_at->toDateString(),
            'created_by' => $foodPrice->createdBy?->name,
        ];
    }

    private function mealFoodItem(StoreFoodPriceRequest $request, FoodItem $foodItem): ?MealFoodItem
    {
        $mealFoodItemId = $request->validated('meal_food_item_id');

        if ($mealFoodItemId === null) {
            return null;
        }

        $mealFoodItem = MealFoodItem::query()
            ->with('meal.project')
            ->findOrFail($mealFoodItemId);

        abort_unless($mealFoodItem->food_item_id === $foodItem->id, 404);

        $this->authorize('update', $mealFoodItem->meal->project);

        return $mealFoodItem;
    }
}
