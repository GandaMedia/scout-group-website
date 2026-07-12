<?php

use App\Enums\UserApprovalStatus;
use App\Models\User;

it('bootstraps a verified user as an approved super administrator', function () {
    $user = User::factory()->pendingApproval()->create([
        'email' => 'admin@example.com',
    ]);

    $this->artisan('users:bootstrap-admin', ['email' => 'admin@example.com'])
        ->expectsOutput('admin@example.com is now an approved super-administrator.')
        ->assertSuccessful();

    $user->refresh();

    expect($user->approval_status)->toBe(UserApprovalStatus::APPROVED)
        ->and($user->hasRole('super-admin'))->toBeTrue()
        ->and($user->can('access admin'))->toBeTrue()
        ->and($user->can('manage leader approvals'))->toBeTrue();
});

it('refuses to bootstrap an unverified user', function () {
    User::factory()->unverified()->pendingApproval()->create([
        'email' => 'admin@example.com',
    ]);

    $this->artisan('users:bootstrap-admin', ['email' => 'admin@example.com'])
        ->expectsOutput('The user must verify their email address first.')
        ->assertFailed();
});
