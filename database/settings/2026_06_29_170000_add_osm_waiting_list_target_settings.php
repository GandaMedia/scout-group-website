<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('osm.target_section_id', '');
        $this->migrator->add('osm.target_term_id', '');
    }
};
