<?php

namespace App\Filament\Resources\WaitingListEntries\Tables;

use App\Enums\Section;
use App\Enums\WaitingListEntrySyncStatus;
use App\Jobs\SyncWaitingListEntry;
use App\Models\WaitingListEntry;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\CarbonImmutable;

class WaitingListEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('full_name')
                    ->label('Child')
                    ->state(fn (WaitingListEntry $record): string => $record->fullName())
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderBy('last_name', $direction)
                            ->orderBy('first_name', $direction);
                    }),
                TextColumn::make('section_slug')
                    ->label('Section')
                    ->formatStateUsing(fn (string $state): string => Section::fromSlug($state)?->value ?? $state)
                    ->badge(),
                TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable(),
                TextColumn::make('parent_name')
                    ->label('Parent / carer')
                    ->searchable(),
                TextColumn::make('parent_email')
                    ->copyable()
                    ->searchable(),
                IconColumn::make('is_possible_duplicate')
                    ->label('Duplicate')
                    ->boolean(),
                TextColumn::make('sync_status')
                    ->badge()
                    ->color(fn (WaitingListEntrySyncStatus $state): string => match ($state) {
                        WaitingListEntrySyncStatus::PENDING => 'warning',
                        WaitingListEntrySyncStatus::HELD_DUPLICATE => 'gray',
                        WaitingListEntrySyncStatus::SYNCING => 'info',
                        WaitingListEntrySyncStatus::SYNCED => 'success',
                        WaitingListEntrySyncStatus::FAILED => 'danger',
                    }),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('synced_at')
                    ->dateTime()
                    ->placeholder('Not synced')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('section_slug')
                    ->label('Section')
                    ->options(Section::optionsBySlug()),
                SelectFilter::make('sync_status')
                    ->options(collect(WaitingListEntrySyncStatus::cases())
                        ->mapWithKeys(fn (WaitingListEntrySyncStatus $status): array => [$status->value => $status->getLabel()])
                        ->all()),
                TernaryFilter::make('is_possible_duplicate')
                    ->label('Duplicate flag'),
                Filter::make('age_band')
                    ->schema([
                        Select::make('value')
                            ->label('Age band')
                            ->options([
                                'under_4' => 'Under 4',
                                '4_6' => '4 to 6',
                                '6_8' => '6 to 8',
                                '8_10_5' => '8 to 10½',
                                '10_5_14' => '10½ to 14',
                                '14_plus' => '14+',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (! is_string($value) || $value === '') {
                            return $query;
                        }

                        $today = CarbonImmutable::today();

                        return match ($value) {
                            'under_4' => $query->whereDate('date_of_birth', '>', $today->subYears(4)),
                            '4_6' => $query
                                ->whereDate('date_of_birth', '<=', $today->subYears(4))
                                ->whereDate('date_of_birth', '>', $today->subYears(6)),
                            '6_8' => $query
                                ->whereDate('date_of_birth', '<=', $today->subYears(6))
                                ->whereDate('date_of_birth', '>', $today->subYears(8)),
                            '8_10_5' => $query
                                ->whereDate('date_of_birth', '<=', $today->subYears(8))
                                ->whereDate('date_of_birth', '>', $today->subYears(10)->subMonths(6)),
                            '10_5_14' => $query
                                ->whereDate('date_of_birth', '<=', $today->subYears(10)->subMonths(6))
                                ->whereDate('date_of_birth', '>', $today->subYears(14)),
                            '14_plus' => $query->whereDate('date_of_birth', '<=', $today->subYears(14)),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('releaseDuplicate')
                    ->label('Release')
                    ->icon(Heroicon::ArrowPathRoundedSquare)
                    ->visible(fn (WaitingListEntry $record): bool => $record->sync_status === WaitingListEntrySyncStatus::HELD_DUPLICATE)
                    ->action(function (WaitingListEntry $record): void {
                        $record->releaseDuplicateHold();

                        Notification::make()
                            ->title('Entry released for sync')
                            ->success()
                            ->send();
                    }),
                Action::make('retrySync')
                    ->label('Retry sync')
                    ->icon(Heroicon::ArrowPath)
                    ->visible(fn (WaitingListEntry $record): bool => $record->sync_status === WaitingListEntrySyncStatus::FAILED)
                    ->action(function (WaitingListEntry $record): void {
                        $record->queueForSync();
                        SyncWaitingListEntry::dispatch($record->getKey());

                        Notification::make()
                            ->title('Sync retry queued')
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
