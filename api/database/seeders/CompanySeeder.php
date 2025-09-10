<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'name' => 'TechSolutions SpA',
                'tax_id' => '96.789.123-4',
                'email' => 'contacto@techsolutions.cl',
                'phone' => '+56 2 2345 6789',
                'address' => 'Av. Providencia 1234, Providencia, Santiago'
            ],
            [
                'name' => 'Constructora Andina Ltda',
                'tax_id' => '78.456.789-0',
                'email' => 'info@constructoraandina.cl',
                'phone' => '+56 2 2987 6543',
                'address' => 'Las Condes 5678, Las Condes, Santiago'
            ],
            [
                'name' => 'Comercial del Norte',
                'tax_id' => '85.234.567-1',
                'email' => 'ventas@comercialnorte.cl',
                'phone' => '+56 55 234 5678',
                'address' => 'Av. Brasil 890, Antofagasta'
            ],
            [
                'name' => 'Servicios Gastronómicos Austral',
                'tax_id' => '92.345.678-2',
                'email' => 'contacto@gastronomicaustral.cl',
                'phone' => '+56 61 345 6789',
                'address' => 'Av. España 456, Punta Arenas'
            ],
            [
                'name' => 'Innovación Digital S.A.',
                'tax_id' => '99.876.543-3',
                'email' => 'hello@innovaciondigital.cl',
                'phone' => '+56 2 3456 7890',
                'address' => 'Av. Libertador Bernardo O\'Higgins 1230, Santiago Centro'
            ]
        ];

        foreach ($companies as $company) {
            Company::create($company);
        }
    }
}
