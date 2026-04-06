<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Hardware', 'sla_config' => ['response_hours' => 8, 'resolution_hours' => 48, 'default_priority' => 'low']],
            ['name' => 'Software', 'sla_config' => ['response_hours' => 4, 'resolution_hours' => 24, 'default_priority' => 'medium']],
            ['name' => 'Redes', 'sla_config' => ['response_hours' => 2, 'resolution_hours' => 12, 'default_priority' => 'high']],
            ['name' => 'Seguridad', 'sla_config' => ['response_hours' => 1, 'resolution_hours' => 4, 'default_priority' => 'critical']],
            ['name' => 'Gestión de Accesos', 'sla_config' => ['response_hours' => 2, 'resolution_hours' => 8, 'default_priority' => 'high']],
            ['name' => 'Correo Electrónico', 'sla_config' => ['response_hours' => 4, 'resolution_hours' => 24, 'default_priority' => 'medium']],
            ['name' => 'Otros', 'sla_config' => ['response_hours' => 8, 'resolution_hours' => 48, 'default_priority' => 'medium']],
        ];

        foreach ($categories as $category) {
            Category::create(array_merge($category, ['active' => true]));
        }
    }
}
