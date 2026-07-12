<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WaitingListEntrySyncStatus: string implements HasLabel
{
    case PENDING = 'pending';
    case HELD_DUPLICATE = 'held_duplicate';
    case SYNCING = 'syncing';
    case SYNCED = 'synced';
    case FAILED = 'failed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::HELD_DUPLICATE => 'Held duplicate',
            self::SYNCING => 'Syncing',
            self::SYNCED => 'Synced',
            self::FAILED => 'Failed',
        };
    }
}
