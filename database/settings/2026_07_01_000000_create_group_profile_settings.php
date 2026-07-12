<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('group_profile.group_name', 'Your Scout Group');
        $this->migrator->add('group_profile.group_short_name', 'Scout Group');
        $this->migrator->add('group_profile.website_url', config('app.url'));
        $this->migrator->add('group_profile.mail_from_name', 'Your Scout Group');
        $this->migrator->add('group_profile.mail_from_address', 'hello@example.org');
        $this->moveContactSetting('recipient_name', 'contact_recipient_name', 'Scout Group Team');
        $this->moveContactSetting('recipient_email', 'contact_recipient_email', 'contact@example.org');
        $this->moveContactSetting('map_label', 'headquarters_label', 'Scout Headquarters');
        $this->moveContactSetting('map_address', 'headquarters_address', "Scout Headquarters\nYour town or city");
        $this->moveContactSetting('map_embed_url', 'map_embed_url', '');
        $this->migrator->add('group_profile.charity_number', 'Configure in admin');
        $this->migrator->add('group_profile.charity_register_url', 'https://www.gov.uk/find-charity-information');
        $this->migrator->add('group_profile.district_name', 'Your Scout District');
        $this->migrator->add('group_profile.district_url', 'https://www.scouts.org.uk/');
    }

    private function moveContactSetting(string $fromName, string $toName, string $default): void
    {
        $from = "contact.{$fromName}";
        $to = "group_profile.{$toName}";

        if ($this->migrator->exists($from)) {
            $this->migrator->rename($from, $to);

            return;
        }

        $this->migrator->add($to, $default);
    }
};
