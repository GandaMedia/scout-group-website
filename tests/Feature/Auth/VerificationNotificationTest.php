<?php

use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
use App\Settings\GroupProfileSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

test('sends verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('home'));

    Notification::assertSentTo($user, QueuedVerifyEmail::class, function (QueuedVerifyEmail $notification): bool {
        return $notification instanceof ShouldQueue && $notification->afterCommit === true;
    });
});

test('verification notification uses group profile mail branding', function () {
    Notification::fake();

    $groupProfileSettings = app(GroupProfileSettings::class);
    $groupProfileSettings->group_short_name = 'Example Scouts';
    $groupProfileSettings->mail_from_name = 'Example Scout Group';
    $groupProfileSettings->mail_from_address = 'hello@example-scouts.test';
    $groupProfileSettings->save();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'));

    Notification::assertSentTo($user, QueuedVerifyEmail::class, function (QueuedVerifyEmail $notification) use ($user): bool {
        $message = $notification->toMail($user);

        return $message->subject === 'Verify your email address'
            && $message->greeting === 'Welcome to Example Scouts'
            && $message->from === ['hello@example-scouts.test', 'Example Scout Group']
            && $message->theme === 'scouts';
    });
});

test('does not send verification notification if email is verified', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard', absolute: false));

    Notification::assertNothingSent();
});
