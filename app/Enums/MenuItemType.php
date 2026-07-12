<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MenuItemType: string implements HasLabel
{
    case LINK = 'LINK';
    case MODEL = 'MODEL';
    case SUBMENU = 'SUBMENU';

    public function getLabel(): ?string
    {

        return match ($this) {
            self::LINK => 'Link',
            self::MODEL => 'Page',
            self::SUBMENU => 'Sub Menu',
        };
    }
}
