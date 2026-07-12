<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class HomePageSettings extends Settings
{
    public array $section_cards;

    public static function group(): string
    {
        return 'home_page';
    }
}
