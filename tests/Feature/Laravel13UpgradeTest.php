<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

it('keeps Laravel 13 upgrade-sensitive cache and session defaults explicit', function () {
    expect(config('cache.prefix'))
        ->toBe(Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_')
        ->and(config('database.redis.options.prefix'))
        ->toBe(Str::slug(env('APP_NAME', 'laravel'), '_').'_database_')
        ->and(config('session.cookie'))
        ->toBe(Str::slug(env('APP_NAME', 'laravel'), '_').'_session')
        ->and(config('cache.serializable_classes'))
        ->toBeFalse();
});

it('renders the Filament admin login page', function () {
    $this->get(route('filament.admin.auth.login'))
        ->assertSuccessful();
});

it('renders the app login page when the main menu is missing', function () {
    Cache::forget('mainMenu');

    $this->get(route('login'))
        ->assertSuccessful();
});
