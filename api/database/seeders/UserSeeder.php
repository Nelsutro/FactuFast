<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // Administrador del sistema
            [
                'name' => 'Administrador Sistema',
                'email' => 'admin@factufast.cl',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'company_id' => null,
                'company_name' => null,
                'email_verified_at' => now()
            ],
            // Usuarios de TechSolutions SpA
            [
                'name' => 'Carlos Mendoza',
                'email' => 'carlos@techsolutions.cl',
                'password' => Hash::make('password123'),
                'role' => 'client',
                'company_id' => 1,
                'company_name' => 'TechSolutions SpA',
                'email_verified_at' => now()
            ],
            [
                'name' => 'Ana García',
                'email' => 'ana@techsolutions.cl',
                'password' => Hash::make('password123'),
                'role' => 'client',
                'company_id' => 1,
                'company_name' => 'TechSolutions SpA',
                'email_verified_at' => now()
            ],
            // Usuarios de Constructora Andina
            [
                'name' => 'Roberto Silva',
                'email' => 'roberto@constructoraandina.cl',
                'password' => Hash::make('password123'),
                'role' => 'client',
                'company_id' => 2,
                'company_name' => 'Constructora Andina Ltda',
                'email_verified_at' => now()
            ],
            [
                'name' => 'María Fernández',
                'email' => 'maria@constructoraandina.cl',
                'password' => Hash::make('password123'),
                'role' => 'client',
                'company_id' => 2,
                'company_name' => 'Constructora Andina Ltda',
                'email_verified_at' => now()
            ],
            // Usuarios de Comercial del Norte
            [
                'name' => 'Pedro Vargas',
                'email' => 'pedro@comercialnorte.cl',
                'password' => Hash::make('password123'),
                'role' => 'client',
                'company_id' => 3,
                'company_name' => 'Comercial del Norte',
                'email_verified_at' => now()
            ],
            // Usuarios de Servicios Gastronómicos Austral
            [
                'name' => 'Sofía Morales',
                'email' => 'sofia@gastronomicaustral.cl',
                'password' => Hash::make('password123'),
                'role' => 'client',
                'company_id' => 4,
                'company_name' => 'Servicios Gastronómicos Austral',
                'email_verified_at' => now()
            ],
            // Usuarios de Innovación Digital
            [
                'name' => 'Diego Torres',
                'email' => 'diego@innovaciondigital.cl',
                'password' => Hash::make('password123'),
                'role' => 'client',
                'company_id' => 5,
                'company_name' => 'Innovación Digital S.A.',
                'email_verified_at' => now()
            ],
            [
                'name' => 'Valentina López',
                'email' => 'valentina@innovaciondigital.cl',
                'password' => Hash::make('password123'),
                'role' => 'client',
                'company_id' => 5,
                'company_name' => 'Innovación Digital S.A.',
                'email_verified_at' => now()
            ]
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
