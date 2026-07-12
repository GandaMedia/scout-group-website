<?php

namespace App\Filament\Resources\LeaderSections\Pages;

use App\Filament\Resources\LeaderSections\LeaderSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeaderSections extends ListRecords
{
    protected static string $resource = LeaderSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
