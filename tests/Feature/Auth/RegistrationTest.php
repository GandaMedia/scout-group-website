<?php

use App\Models\User;
use App\Notifications\LeaderRegistrationSubmitted;
use App\Notifications\QueuedVerifyEmail;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register and are sent a verification notification', function () {
    Notification::fake();
    $this->seed(RolesAndPermissionsSeeder::class);
    $reviewer = User::factory()->create();
    $reviewer->givePermissionTo('manage leader approvals');

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'test@example.com')->sole();

    expect($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->approval_status->value)->toBe('pending')
        ->and($user->can('access leader tools'))->toBeFalse()
        ->and($user->can('access admin'))->toBeFalse();
    Notification::assertSentTo($user, QueuedVerifyEmail::class, function (QueuedVerifyEmail $notification): bool {
        return $notification instanceof ShouldQueue && $notification->afterCommit === true;
    });
    Notification::assertSentTo($reviewer, LeaderRegistrationSubmitted::class, function (LeaderRegistrationSubmitted $notification): bool {
        return $notification instanceof ShouldQueue && $notification->afterCommit === true;
    });
});
