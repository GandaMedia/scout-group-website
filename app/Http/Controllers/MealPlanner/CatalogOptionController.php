<?php

namespace App\Http\Controllers\MealPlanner;

use App\Http\Controllers\Controller;
use App\Http\Requests\MealPlanner\StoreCatalogOptionRequest;
use App\Models\Brand;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CatalogOptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type') === 'store' ? 'store' : 'brand';
        $query = trim((string) $request->query('q', ''));
        $model = $type === 'store' ? Store::class : Brand::class;

        return response()->json([
            'data' => $model::query()
                ->when(filled($query), fn ($builder) => $builder->where('name', 'like', "%{$query}%"))
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'name'])
                ->map(fn (Model $record): array => [
                    'id' => $record->id,
                    'name' => $record->name,
                    'type' => $type,
                ]),
        ]);
    }

    public function store(StoreCatalogOptionRequest $request): JsonResponse|RedirectResponse
    {
        $type = $request->validated('type');
        $model = $type === 'store' ? Store::class : Brand::class;
        $normalizedName = Str::of($request->validated('name'))->lower()->squish()->toString();

        $record = $model::query()->firstOrCreate(
            ['normalized_name' => $normalizedName],
            [
                'name' => $request->validated('name'),
                'created_by_user_id' => $request->user()->id,
            ],
        );

        if ($request->expectsJson()) {
            return response()->json([
                'data' => [
                    'id' => $record->id,
                    'name' => $record->name,
                    'type' => $type,
                ],
            ], JsonResponse::HTTP_CREATED);
        }

        return back();
    }
}
