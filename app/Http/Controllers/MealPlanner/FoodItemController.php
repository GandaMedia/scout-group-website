<?php

namespace App\Http\Controllers\MealPlanner;

use App\Http\Controllers\Controller;
use App\Http\Requests\MealPlanner\StoreFoodItemRequest;
use App\Models\FoodItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FoodItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        $foodItems = filled($query)
            ? FoodItem::search($query)->query(fn ($builder) => $builder->with(['brand', 'store', 'latestPrice']))->take(20)->get()
            : FoodItem::query()->with(['brand', 'store', 'latestPrice'])->latest()->limit(20)->get();

        return response()->json([
            'data' => $foodItems->map(fn (FoodItem $foodItem): array => [
                'id' => $foodItem->id,
                'name' => $foodItem->name,
                'brand' => $foodItem->brand?->name,
                'store' => $foodItem->store?->name,
                'servings_per_pack' => $foodItem->servings_per_pack,
                'calories_per_pack' => $foodItem->calories_per_pack,
                'latest_price_id' => $foodItem->latestPrice?->id,
                'latest_price_minor' => $foodItem->latestPrice?->price_per_pack?->getMinorAmount()->toInt(),
                'latest_priced_at' => $foodItem->latestPrice?->priced_at?->toDateString(),
            ])->values(),
        ]);
    }

    public function store(StoreFoodItemRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', FoodItem::class);

        $foodItem = DB::transaction(function () use ($request): FoodItem {
            $foodItem = FoodItem::query()->create([
                'name' => $request->validated('name'),
                'brand_id' => $request->validated('brand_id'),
                'store_id' => $request->validated('store_id'),
                'servings_per_pack' => $request->validated('servings_per_pack'),
                'calories_per_pack' => $request->validated('calories_per_pack'),
                'created_by_user_id' => $request->user()->id,
            ]);

            $foodItem->prices()->create([
                'price_per_pack' => $request->validated('price_per_pack'),
                'priced_at' => $request->validated('priced_at'),
                'created_by_user_id' => $request->user()->id,
            ]);

            return $foodItem->load(['brand', 'store', 'latestPrice']);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $this->foodItemPayload($foodItem),
            ], JsonResponse::HTTP_CREATED);
        }

        return back();
    }

    public function destroy(FoodItem $foodItem): RedirectResponse
    {
        $this->authorize('delete', $foodItem);

        $foodItem->delete();

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function foodItemPayload(FoodItem $foodItem): array
    {
        return [
            'id' => $foodItem->id,
            'name' => $foodItem->name,
            'brand' => $foodItem->brand?->name,
            'store' => $foodItem->store?->name,
            'servings_per_pack' => $foodItem->servings_per_pack,
            'calories_per_pack' => $foodItem->calories_per_pack,
            'latest_price_id' => $foodItem->latestPrice?->id,
            'latest_price_minor' => $foodItem->latestPrice?->price_per_pack?->getMinorAmount()->toInt(),
            'latest_priced_at' => $foodItem->latestPrice?->priced_at?->toDateString(),
        ];
    }
}
