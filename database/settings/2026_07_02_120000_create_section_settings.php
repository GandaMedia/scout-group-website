<?php

use App\Enums\Section;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('sections.enabled_section_slugs', Section::slugs());
    }
};
