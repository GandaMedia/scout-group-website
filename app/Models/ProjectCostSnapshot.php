<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Database\Factories\ProjectCostSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ProjectCostSnapshot extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<ProjectCostSnapshotFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'created_by_user_id',
        'total_cost',
        'cost_per_head',
        'total_calories_per_serving',
        'meal_count',
        'snapshot_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_cost' => MoneyCast::class.':GBP',
            'cost_per_head' => MoneyCast::class.':GBP',
            'total_calories_per_serving' => 'integer',
            'meal_count' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
