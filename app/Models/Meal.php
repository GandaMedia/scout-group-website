<?php

namespace App\Models;

use App\Enums\MealType;
use Database\Factories\MealFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Meal extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<MealFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'meal_type',
        'day_number',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meal_type' => MealType::class,
            'day_number' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function mealFoodItems(): HasMany
    {
        return $this->hasMany(MealFoodItem::class);
    }
}
