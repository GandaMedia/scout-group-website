<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('osm.client_id', '');
        $this->migrator->addEncrypted('osm.client_secret', '');
        $this->migrator->add('osm.redirect_uri', '');
        $this->migrator->addEncrypted('osm.authorization_code', '');
        $this->migrator->addEncrypted('osm.refresh_token', '');
        $this->migrator->addEncrypted('osm.access_token', '');
        $this->migrator->add('osm.access_token_expires_at', null);
        $this->migrator->add('osm.squirrels_section_id', '');
        $this->migrator->add('osm.squirrels_term_id', '');
        $this->migrator->add('osm.beavers_section_id', '');
        $this->migrator->add('osm.beavers_term_id', '');
        $this->migrator->add('osm.cubs_section_id', '');
        $this->migrator->add('osm.cubs_term_id', '');
        $this->migrator->add('osm.scouts_section_id', '');
        $this->migrator->add('osm.scouts_term_id', '');
    }
};
