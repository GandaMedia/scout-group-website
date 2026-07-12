<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GroupProfileSettings extends Settings
{
    public string $group_name;

    public string $group_short_name;

    public string $logo_short_label;

    public string $logo_stacked_line_1;

    public string $logo_stacked_line_2;

    public string $website_url;

    public string $mail_from_name;

    public string $mail_from_address;

    public string $contact_recipient_name;

    public string $contact_recipient_email;

    public string $headquarters_label;

    public string $headquarters_address;

    public string $map_embed_url;

    public string $charity_number;

    public string $charity_register_url;

    public string $district_name;

    public string $district_url;

    public static function group(): string
    {
        return 'group_profile';
    }
}
