<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ContactSettings extends Settings
{
    public string $success_message;

    public static function group(): string
    {
        return 'contact';
    }
}
