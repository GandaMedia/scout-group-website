<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserApprovalStatus;
use App\Filament\AdminNavigationGroup;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Notifications\LeaderRegistrationDecision;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Administration;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Users';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not verified'),
                TextColumn::make('approval_status')
                    ->label('Leader access')
                    ->badge(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(','),
                TextColumn::make('two_factor_status')
                    ->label('2FA')
                    ->badge()
                    ->state(fn (User $record): string => $record->hasEnabledTwoFactorAuthentication() ? 'Enabled' : 'Disabled')
                    ->color(fn (string $state): string => $state === 'Enabled' ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('approveLeader')
                    ->label('Approve leader')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => auth()->user()?->can('manage leader approvals') === true
                        && $record->approval_status !== UserApprovalStatus::APPROVED)
                    ->action(function (User $record): void {
                        $record->forceFill([
                            'approval_status' => UserApprovalStatus::APPROVED,
                            'approved_at' => now(),
                            'approved_by_user_id' => auth()->id(),
                            'rejected_at' => null,
                        ])->save();
                        $record->assignRole('leader');
                        $record->notify(new LeaderRegistrationDecision(UserApprovalStatus::APPROVED));
                    })
                    ->successNotificationTitle('Leader account approved'),
                Action::make('rejectLeader')
                    ->label('Reject request')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => auth()->user()?->can('manage leader approvals') === true
                        && ! $record->hasRole('super-admin')
                        && ! $record->is(auth()->user())
                        && $record->approval_status !== UserApprovalStatus::REJECTED)
                    ->action(function (User $record): void {
                        $record->forceFill([
                            'approval_status' => UserApprovalStatus::REJECTED,
                            'approved_at' => null,
                            'approved_by_user_id' => auth()->id(),
                            'rejected_at' => now(),
                        ])->save();
                        $record->removeRole('leader');
                        $record->revokePermissionTo('access leader tools');
                        $record->revokePermissionTo('access admin');
                        $record->notify(new LeaderRegistrationDecision(UserApprovalStatus::REJECTED));
                    })
                    ->successNotificationTitle('Leader request rejected'),
                Action::make('grantAdminAccess')
                    ->label('Grant admin access')
                    ->icon(Heroicon::OutlinedLockOpen)
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => auth()->user()?->can('manage leader approvals') === true
                        && ! $record->can('access admin'))
                    ->action(fn (User $record) => $record->givePermissionTo('access admin'))
                    ->successNotificationTitle('Admin access granted'),
                Action::make('revokeAdminAccess')
                    ->label('Revoke admin access')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => auth()->user()?->can('manage leader approvals') === true
                        && ! $record->hasRole('super-admin')
                        && ! $record->is(auth()->user())
                        && $record->can('access admin'))
                    ->action(fn (User $record) => $record->revokePermissionTo('access admin'))
                    ->successNotificationTitle('Admin access revoked'),
                Action::make('resetTwoFactorAuthentication')
                    ->label('Reset 2FA')
                    ->icon(Heroicon::OutlinedShieldExclamation)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset two-factor authentication')
                    ->modalDescription(fn (User $record): string => "This will disable two-factor authentication for {$record->name}. They can set it up again from their account settings.")
                    ->modalSubmitActionLabel('Reset 2FA')
                    ->visible(fn (User $record): bool => filled($record->two_factor_secret)
                        || filled($record->two_factor_recovery_codes)
                        || filled($record->two_factor_confirmed_at))
                    ->action(function (User $record): void {
                        $record->forceFill([
                            'two_factor_secret' => null,
                            'two_factor_recovery_codes' => null,
                            'two_factor_confirmed_at' => null,
                        ])->save();
                    })
                    ->successNotificationTitle('Two-factor authentication reset'),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
