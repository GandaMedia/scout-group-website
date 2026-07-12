<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('osm.directory_account_name', null);
        $this->migrator->add('osm.directory_account_email', null);
        $this->migrator->add('osm.directory_sections', '{}');
        $this->migrator->add('osm.directory_terms_by_section', '{}');
        $this->migrator->add('osm.directory_refreshed_at', null);
        $this->migrator->add('osm.directory_refresh_queued_at', null);
        $this->migrator->add('osm.directory_last_error', null);
    }
};
