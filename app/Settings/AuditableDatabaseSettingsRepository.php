<?php

namespace App\Settings;

use Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository;

class AuditableDatabaseSettingsRepository extends DatabaseSettingsRepository
{
    public function updatePropertiesPayload(string $group, array $properties): void
    {
        foreach ($properties as $name => $payload) {
            $this->getBuilder()->updateOrCreate(
                ['group' => $group, 'name' => $name],
                ['payload' => $this->encode($payload)],
            );
        }
    }
}
