<?php

use App\Enums\UserApprovalStatus;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Notifications\LeaderRegistrationDecision;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $this->actingAs($admin);
});

it('approves a pending leader without granting admin access', function () {
    Notification::fake();
    $user = User::factory()->pendingApproval()->create();

    Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('approveLeader')->table($user))
        ->assertNotified();

    $user->refresh();

    expect($user->approval_status)->toBe(UserApprovalStatus::APPROVED)
        ->and($user->can('access leader tools'))->toBeTrue()
        ->and($user->can('access admin'))->toBeFalse()
        ->and($user->approved_by_user_id)->toBe(auth()->id());

    Notification::assertSentTo($user, LeaderRegistrationDecision::class, function (LeaderRegistrationDecision $notification): bool {
        return $notification instanceof ShouldQueue && $notification->afterCommit === true;
    });
});

it('grants admin access independently from leader approval', function () {
    $user = User::factory()->pendingApproval()->create();

    Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('grantAdminAccess')->table($user))
        ->assertNotified();

    expect($user->refresh()->can('access admin'))->toBeTrue()
        ->and($user->can('access leader tools'))->toBeFalse()
        ->and($user->approval_status)->toBe(UserApprovalStatus::PENDING);
});

it('lists users and exposes the two factor reset action only when configured', function () {
    $twoFactorUser = User::factory()->create([
        'name' => 'Two Factor User',
        'email' => 'two-factor@example.com',
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $standardUser = User::factory()->withoutTwoFactor()->create([
        'name' => 'Standard User',
        'email' => 'standard@example.com',
    ]);

    Livewire::test(ManageUsers::class)
        ->assertCanSeeTableRecords([$twoFactorUser, $standardUser])
        ->assertActionVisible(TestAction::make('resetTwoFactorAuthentication')->table($twoFactorUser))
        ->assertActionHidden(TestAction::make('resetTwoFactorAuthentication')->table($standardUser));
});

it('resets two factor authentication for a user from the table action', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ]);

    Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('resetTwoFactorAuthentication')->table($user))
        ->assertNotified();

    $user->refresh();

    expect($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
});
