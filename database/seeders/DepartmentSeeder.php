<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            'Soporte Técnico',
            'Sistemas',
            'Administración',
            'Marketing',
            'Recursos Humanos',
            'Finanzas',
            'Operaciones',
            'Ventas',
            'Dirección Ejecutiva',
        ];

        foreach ($departments as $name) {
            Department::create(['name' => $name, 'active' => true]);
        }
    }
}
