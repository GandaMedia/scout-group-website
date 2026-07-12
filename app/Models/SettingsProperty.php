<?php

namespace App\Models;

use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\LaravelSettings\Models\SettingsProperty as BaseSettingsProperty;

class SettingsProperty extends BaseSettingsProperty implements AuditableContract
{
    use Auditable {
        readyForAuditing as auditableReadyForAuditing;
    }

    /**
     * OSM operational state can contain secrets, personal data, or large machine-generated snapshots.
     *
     * @var list<string>
     */
    private const OSM_AUDIT_EXCLUSIONS = [
        'access_token',
        'refresh_token',
        'access_token_expires_at',
        'directory_account_name',
        'directory_account_email',
        'directory_sections',
        'directory_terms_by_section',
        'directory_refreshed_at',
        'directory_refresh_queued_at',
        'directory_last_error',
    ];

    public function readyForAuditing(): bool
    {
        if ($this->group === 'osm' && in_array($this->name, self::OSM_AUDIT_EXCLUSIONS, true)) {
            return false;
        }

        return $this->auditableReadyForAuditing();
    }

    /**
     * Store decoded setting values so audit records remain readable.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function transformAudit(array $data): array
    {
        foreach (['old_values', 'new_values'] as $valuesKey) {
            if (isset($data[$valuesKey]['payload']) && is_string($data[$valuesKey]['payload'])) {
                $data[$valuesKey]['payload'] = json_decode($data[$valuesKey]['payload'], true, flags: JSON_THROW_ON_ERROR);
            }
        }

        return $data;
    }
}
