<?php

namespace App\Filament\Resources\LeaderSections\Tables;

use App\Enums\Section;
use App\Models\LeaderSection;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaderSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('leader_photo')
                    ->state(fn (LeaderSection $record): ?string => $record->leader?->photoUrl('thumb'))
                    ->label('Photo'),
                TextColumn::make('leader.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('section')
                    ->badge()
                    ->sortable(),
                TextColumn::make('order_column')
                    ->label('Order')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('section')
                    ->options(Section::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('order_column')
            ->defaultSort('order_column');
    }
}
