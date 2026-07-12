<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CalendarFeedSyncStatus: string implements HasColor, HasLabel
{
    case NEVER = 'never';
    case SUCCESS = 'success';
    case FAILED = 'failed';

    public function getColor(): string
    {
        return match ($this) {
            self::NEVER => 'gray',
            self::SUCCESS => 'success',
            self::FAILED => 'danger',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NEVER => 'Never synced',
            self::SUCCESS => 'Last sync succeeded',
            self::FAILED => 'Last sync failed',
        };
    }
}
