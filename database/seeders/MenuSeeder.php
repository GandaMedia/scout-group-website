<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Menu::query()->firstOrCreate(['name' => 'Main Menu']);
        Menu::query()->firstOrCreate(['name' => 'Footer Menu']);
    }
}
