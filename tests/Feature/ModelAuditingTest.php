<?php

use App\Models\Project;
use App\Models\SettingsProperty;
use App\Models\User;
use App\Settings\ContactSettings;
use App\Settings\OsmSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

beforeEach(function () {
    config(['audit.console' => true]);
});

test('auditable models write audit records', function () {
    $user = User::factory()->create();

    $project = Project::factory()->for($user)->create(['name' => 'Original']);
    $project->update(['name' => 'Updated']);
    $project->delete();

    $events = DB::table('audits')
        ->where('auditable_type', Project::class)
        ->where('auditable_id', $project->id)
        ->pluck('event')
        ->all();

    expect($events)->toContain('created', 'updated', 'deleted');
});

test('user audit records exclude sensitive authentication fields', function () {
    $user = User::factory()->create();

    $user->forceFill([
        'password' => 'changed-password',
        'remember_token' => 'changed-token',
        'two_factor_secret' => 'changed-secret',
        'two_factor_recovery_codes' => 'changed-codes',
    ])->save();

    $audit = DB::table('audits')
        ->where('auditable_type', User::class)
        ->where('auditable_id', $user->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull();

    $newValues = json_decode($audit->new_values, true);

    expect($newValues)->not->toHaveKeys([
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ]);
});

test('all application models are auditable', function () {
    $modelClasses = collect(File::files(app_path('Models')))
        ->map(fn (SplFileInfo $file): string => 'App\\Models\\'.$file->getBasename('.php'))
        ->filter(fn (string $class): bool => class_exists($class) && is_subclass_of($class, Model::class));

    expect($modelClasses)->not->toBeEmpty();

    $modelClasses->each(fn (string $class) => expect($class)->toImplement(AuditableContract::class));
});

test('settings changes write readable audit records for the authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $settings = app(ContactSettings::class);
    $originalMessage = $settings->success_message;
    $settings->success_message = 'Updated contact confirmation.';
    $settings->save();

    $property = SettingsProperty::query()
        ->where('group', ContactSettings::group())
        ->where('name', 'success_message')
        ->sole();

    $audit = DB::table('audits')
        ->where('auditable_type', SettingsProperty::class)
        ->where('auditable_id', $property->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->user_id)->toBe($user->id)
        ->and(json_decode($audit->old_values, true)['payload'])->toBe($originalMessage)
        ->and(json_decode($audit->new_values, true)['payload'])->toBe('Updated contact confirmation.');
});

test('OSM secrets and operational snapshots are not audited', function () {
    $settings = app(OsmSettings::class);
    $settings->access_token = 'new-secret-access-token';
    $settings->refresh_token = 'new-secret-refresh-token';
    $settings->directory_account_email = 'private@example.test';
    $settings->directory_sections = json_encode(['1' => 'Squirrels'], JSON_THROW_ON_ERROR);
    $settings->directory_refreshed_at = now()->toIso8601String();
    $settings->save();

    $excludedPropertyIds = SettingsProperty::query()
        ->where('group', OsmSettings::group())
        ->whereIn('name', [
            'access_token',
            'refresh_token',
            'directory_account_email',
            'directory_sections',
            'directory_refreshed_at',
        ])
        ->pluck('id');

    expect(DB::table('audits')
        ->where('auditable_type', SettingsProperty::class)
        ->whereIn('auditable_id', $excludedPropertyIds)
        ->exists())->toBeFalse();

    $settings->target_section_id = 'human-managed-section';
    $settings->save();

    $managedProperty = SettingsProperty::query()
        ->where('group', OsmSettings::group())
        ->where('name', 'target_section_id')
        ->sole();

    expect(DB::table('audits')
        ->where('auditable_type', SettingsProperty::class)
        ->where('auditable_id', $managedProperty->id)
        ->where('event', 'updated')
        ->exists())->toBeTrue();
});
