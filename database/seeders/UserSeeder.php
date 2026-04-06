<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed users to match the frontend SQL sample data.
        $defaultPassword = Hash::make('Control123+');

        User::create([
            'first_name' => 'Root',
            'last_name' => 'Admin',
            'email' => 'admin@cosabe.edu.bo',
            'email_verified_at' => now(),
            'password' => $defaultPassword,
            'password_hash' => $defaultPassword,
            'role' => 'Admin',
            'department_id' => 1,
            'active' => true,
            'metadata' => [],
        ]);

        // Crear 10 especialistas de Soporte Técnico (ID 1)
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'first_name' => 'Especialista',
                'last_name' => 'IT ' . $i,
                'email' => 'tecnico' . $i . '@cosabe.edu.bo',
                'email_verified_at' => now(),
                'password' => $defaultPassword,
                'password_hash' => $defaultPassword,
                'role' => 'Agent',
                'department_id' => 1,
                'active' => true,
                'metadata' => [],
            ]);
        }

        User::create([
            'first_name' => 'Carlos',
            'last_name' => 'López',
            'email' => 'usuario@cosabe.edu.bo',
            'email_verified_at' => now(),
            'password' => $defaultPassword,
            'password_hash' => $defaultPassword,
            'role' => 'Staff',
            'department_id' => 3,
            'active' => true,
            'metadata' => [],
        ]);

        // Additional random users for development/testing
        User::factory()->count(10)->create();
    }
}
