<?php

namespace App\Filament\Resources\CalendarFeedSources;

use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\CalendarFeedSources\Pages\CreateCalendarFeedSource;
use App\Filament\Resources\CalendarFeedSources\Pages\EditCalendarFeedSource;
use App\Filament\Resources\CalendarFeedSources\Pages\ListCalendarFeedSources;
use App\Filament\Resources\CalendarFeedSources\Schemas\CalendarFeedSourceForm;
use App\Filament\Resources\CalendarFeedSources\Tables\CalendarFeedSourcesTable;
use App\Models\CalendarFeedSource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CalendarFeedSourceResource extends Resource
{
    protected static ?string $model = CalendarFeedSource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Programme;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'section';

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record instanceof CalendarFeedSource) {
            return null;
        }

        return $record->section?->getLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return CalendarFeedSourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CalendarFeedSourcesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCalendarFeedSources::route('/'),
            'create' => CreateCalendarFeedSource::route('/create'),
            'edit' => EditCalendarFeedSource::route('/{record}/edit'),
        ];
    }
}
