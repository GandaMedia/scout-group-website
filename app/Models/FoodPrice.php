<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Database\Factories\FoodPriceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class FoodPrice extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<FoodPriceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'food_item_id',
        'price_per_pack',
        'priced_at',
        'created_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_per_pack' => MoneyCast::class.':GBP',
            'priced_at' => 'date',
        ];
    }

    public function foodItem(): BelongsTo
    {
        return $this->belongsTo(FoodItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
