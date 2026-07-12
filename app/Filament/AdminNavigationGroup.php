<?php

namespace App\Filament;

use Filament\Support\Contracts\HasLabel;

enum AdminNavigationGroup implements HasLabel
{
    case Website;
    case Team;
    case Programme;
    case Enquiries;
    case Settings;
    case Administration;

    public function getLabel(): string
    {
        return match ($this) {
            self::Website => 'Website',
            self::Team => 'Team',
            self::Programme => 'Programme',
            self::Enquiries => 'Enquiries',
            self::Settings => 'Settings',
            self::Administration => 'Administration',
        };
    }
}
