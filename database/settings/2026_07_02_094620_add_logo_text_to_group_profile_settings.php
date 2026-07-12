<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('group_profile.logo_short_label', 'Your Group');
        $this->migrator->add('group_profile.logo_stacked_line_1', 'Your Group');
        $this->migrator->add('group_profile.logo_stacked_line_2', 'Scout Group');
    }
};
