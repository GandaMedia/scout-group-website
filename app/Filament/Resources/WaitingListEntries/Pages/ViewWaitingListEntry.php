<?php

namespace App\Filament\Resources\WaitingListEntries\Pages;

use App\Filament\Resources\WaitingListEntries\WaitingListEntryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWaitingListEntry extends ViewRecord
{
    protected static string $resource = WaitingListEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
