<?php

namespace App\Filament\Resources\ContactEnquiries\Pages;

use App\Filament\Resources\ContactEnquiries\ContactEnquiryResource;
use App\Models\ContactEnquiry;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewContactEnquiry extends ViewRecord
{
    protected static string $resource = ContactEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markReviewed')
                ->label('Mark reviewed')
                ->icon(Heroicon::CheckBadge)
                ->visible(fn (ContactEnquiry $record): bool => $record->reviewed_at === null)
                ->action(function (ContactEnquiry $record): void {
                    $record->markReviewed();

                    Notification::make()
                        ->title('Enquiry marked as reviewed')
                        ->success()
                        ->send();
                }),
        ];
    }
}
