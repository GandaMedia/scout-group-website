<?php

namespace App\Filament\Resources\LeaderSections;

use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\LeaderSections\Pages\CreateLeaderSection;
use App\Filament\Resources\LeaderSections\Pages\EditLeaderSection;
use App\Filament\Resources\LeaderSections\Pages\ListLeaderSections;
use App\Filament\Resources\LeaderSections\Schemas\LeaderSectionForm;
use App\Filament\Resources\LeaderSections\Tables\LeaderSectionsTable;
use App\Models\LeaderSection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LeaderSectionResource extends Resource
{
    protected static ?string $model = LeaderSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Team;

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Leader sections';

    public static function form(Schema $schema): Schema
    {
        return LeaderSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaderSectionsTable::configure($table);
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
            'index' => ListLeaderSections::route('/'),
            'create' => CreateLeaderSection::route('/create'),
            'edit' => EditLeaderSection::route('/{record}/edit'),
        ];
    }
}
