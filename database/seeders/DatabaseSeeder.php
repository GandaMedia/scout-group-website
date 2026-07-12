<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(RolesAndPermissionsSeeder::class);

        $this->call([
            PageSeeder::class,
            TagSeeder::class,
            MenuSeeder::class,
            MenuItemSeeder::class,
        ]);
    }
}
