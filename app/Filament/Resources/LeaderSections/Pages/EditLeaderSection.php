<?php

namespace App\Filament\Resources\LeaderSections\Pages;

use App\Filament\Resources\LeaderSections\LeaderSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLeaderSection extends EditRecord
{
    protected static string $resource = LeaderSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
