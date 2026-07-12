<?php

namespace App\Models;

use App\Enums\MenuItemType;
use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class MenuItem extends Model implements AuditableContract, Sortable
{
    use Auditable;

    /** @use HasFactory<MenuItemFactory> */
    use HasFactory;

    use HasRecursiveRelationships;
    use SoftDeletes;
    use SortableTrait;

    public $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    protected $fillable = [
        'name',
        'menu_id',
        'parent_id',
        'type',
        'menuable_id',
        'menuable_type',
        'link',
    ];

    protected static function booted(): void
    {
        self::saved(static function (): void {
            Cache::forget('mainMenu');
        });
    }

    protected function casts(): array
    {
        return [
            'type' => MenuItemType::class,
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function menuable(): MorphTo
    {
        return $this->morphTo();
    }
}
