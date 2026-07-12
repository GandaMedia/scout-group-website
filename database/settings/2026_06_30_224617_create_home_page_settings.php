<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('home_page.section_cards', [
            [
                'section' => 'Squirrels',
                'age_range' => '4 - 6 years old',
                'time_slot' => 'Configure meeting details in admin',
                'description' => "Helping young people gain skills for life at a time when it\nmatters most and where it's most needed",
                'page_slug' => 'squirrels',
            ],
            [
                'section' => 'Beavers',
                'age_range' => '6 - 8 years old',
                'time_slot' => 'Configure meeting details in admin',
                'description' => 'Try new things. Make new friends. Joining Beavers is just the beginning of your big adventure.',
                'page_slug' => 'beavers',
            ],
            [
                'section' => 'Cubs',
                'age_range' => '8 - 10½ years old',
                'time_slot' => 'Configure meeting details in admin',
                'description' => 'Develop new skills. Soar to great heights. Being a Cub opens up a whole other world.',
                'page_slug' => 'cubs',
            ],
            [
                'section' => 'Scouts',
                'age_range' => '10½ - 14 years old',
                'time_slot' => 'Configure meeting details in admin',
                'description' => 'Jump in and get muddy. Give back and get set. Scouts ignore the butterflies and go for it, and soon so will you.',
                'page_slug' => 'scouts',
            ],
        ]);
    }
};
