<?php

namespace App\Filament\Resources\ContactEnquiries;

use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\ContactEnquiries\Pages\ListContactEnquiries;
use App\Filament\Resources\ContactEnquiries\Pages\ViewContactEnquiry;
use App\Filament\Resources\ContactEnquiries\Schemas\ContactEnquiryForm;
use App\Filament\Resources\ContactEnquiries\Schemas\ContactEnquiryInfolist;
use App\Filament\Resources\ContactEnquiries\Tables\ContactEnquiriesTable;
use App\Models\ContactEnquiry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ContactEnquiryResource extends Resource
{
    protected static ?string $model = ContactEnquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Enquiries;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Contact enquiries';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return ContactEnquiryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContactEnquiryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactEnquiriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactEnquiries::route('/'),
            'view' => ViewContactEnquiry::route('/{record}'),
        ];
    }
}
