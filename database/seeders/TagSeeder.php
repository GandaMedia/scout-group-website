<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->sectionTags() as $tagData) {
            Tag::query()->updateOrCreate(
                ['slug' => $tagData['slug']],
                ['name' => $tagData['name']],
            );
        }
    }

    /**
     * @return list<array{name: string, slug: string}>
     */
    private function sectionTags(): array
    {
        return [
            ['name' => 'Squirrels', 'slug' => 'squirrels'],
            ['name' => 'Beavers', 'slug' => 'beavers'],
            ['name' => 'Cubs', 'slug' => 'cubs'],
            ['name' => 'Scouts', 'slug' => 'scouts'],
            ['name' => 'Explorers', 'slug' => 'explorers'],
            ['name' => 'Network', 'slug' => 'network'],
        ];
    }
}
