<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('contact.success_message', 'Thanks for getting in touch. We will come back to you soon.');
    }
};
