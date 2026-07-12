<?php

namespace App\Models;

use Database\Factories\ContactEnquiryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ContactEnquiry extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<ContactEnquiryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'message',
        'submitted_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function markReviewed(): void
    {
        if ($this->reviewed_at instanceof Carbon) {
            return;
        }

        $this->forceFill([
            'reviewed_at' => now(),
        ])->save();
    }
}
