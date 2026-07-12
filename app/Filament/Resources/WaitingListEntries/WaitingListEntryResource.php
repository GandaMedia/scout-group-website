<?php

namespace App\Filament\Resources\WaitingListEntries;

use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\WaitingListEntries\Pages\EditWaitingListEntry;
use App\Filament\Resources\WaitingListEntries\Pages\ListWaitingListEntries;
use App\Filament\Resources\WaitingListEntries\Pages\ViewWaitingListEntry;
use App\Filament\Resources\WaitingListEntries\Schemas\WaitingListEntryForm;
use App\Filament\Resources\WaitingListEntries\Schemas\WaitingListEntryInfolist;
use App\Filament\Resources\WaitingListEntries\Tables\WaitingListEntriesTable;
use App\Models\WaitingListEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WaitingListEntryResource extends Resource
{
    protected static ?string $model = WaitingListEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Enquiries;

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static ?string $navigationLabel = 'Waiting list';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return WaitingListEntryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WaitingListEntryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WaitingListEntriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWaitingListEntries::route('/'),
            'view' => ViewWaitingListEntry::route('/{record}'),
            'edit' => EditWaitingListEntry::route('/{record}/edit'),
        ];
    }
}
