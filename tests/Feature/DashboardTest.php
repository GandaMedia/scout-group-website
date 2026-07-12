<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('auth.approvalStatus', 'approved'));
});

test('pending users can visit the dashboard but cannot access leader tools', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->pendingApproval()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.approvalStatus', 'pending')
            ->where('auth.canAccessLeaderTools', false)
            ->where('auth.canAccessAdmin', false));

    $this->actingAs($user)
        ->get(route('tools.projects'))
        ->assertForbidden();
});

test('leader tools and admin access are independent permissions', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $leader = User::factory()->create();
    $leader->givePermissionTo('access leader tools');

    $this->actingAs($leader)
        ->get(route('tools.projects'))
        ->assertSuccessful();

    expect($leader->can('access admin'))->toBeFalse();
});

test('unverified authenticated users are redirected to the verification notice', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('verification.notice'));
});
