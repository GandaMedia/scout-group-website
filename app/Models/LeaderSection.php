<?php

namespace App\Models;

use App\Enums\Section;
use Database\Factories\LeaderSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class LeaderSection extends Model implements AuditableContract, Sortable
{
    use Auditable;

    /** @use HasFactory<LeaderSectionFactory> */
    use HasFactory;

    use SortableTrait;

    /**
     * @var array<string, mixed>
     */
    public $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'leader_id',
        'section',
        'order_column',
    ];

    protected function casts(): array
    {
        return [
            'section' => Section::class,
        ];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Leader::class);
    }
}
