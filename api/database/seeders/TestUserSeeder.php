<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador de prueba
        User::firstOrCreate(
            ['email' => 'admin@factufast.com'],
            [
                'name' => 'Administrador FactuFast',
                'email' => 'admin@factufast.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Crear usuario cliente de prueba
        User::firstOrCreate(
            ['email' => 'cliente@test.com'],
            [
                'name' => 'Cliente de Prueba',
                'email' => 'cliente@test.com',
                'password' => Hash::make('password123'),
                'role' => 'client',
                'email_verified_at' => now(),
            ]
        );

        // Crear usuario staff de prueba
        User::firstOrCreate(
            ['email' => 'staff@test.com'],
            [
                'name' => 'Staff de Prueba',
                'email' => 'staff@test.com',
                'password' => Hash::make('password123'),
                'role' => 'staff',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Usuarios de prueba creados exitosamente');
    }
}
