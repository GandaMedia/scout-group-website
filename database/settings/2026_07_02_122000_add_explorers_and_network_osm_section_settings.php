<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('osm.explorers_section_id', '');
        $this->migrator->add('osm.explorers_term_id', '');
        $this->migrator->add('osm.network_section_id', '');
        $this->migrator->add('osm.network_term_id', '');
    }
};
