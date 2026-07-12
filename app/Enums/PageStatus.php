<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PageStatus: string implements HasLabel
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';

    public function getLabel(): ?string
    {
        return $this->name;

    }
}
