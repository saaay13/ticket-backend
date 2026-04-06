<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // order matters because of foreign key relationships
        $this->call([
            DepartmentSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            TicketSeeder::class,
            TemplateSeeder::class,
        ]);
    }
}
