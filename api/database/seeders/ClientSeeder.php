<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            // Clientes de TechSolutions SpA (company_id: 1)
            [
                'company_id' => 1,
                'name' => 'Banco Central de Chile',
                'email' => 'facturacion@bcentral.cl',
                'phone' => '+56 2 2670 2000',
                'address' => 'Agustinas 1180, Santiago Centro'
            ],
            [
                'company_id' => 1,
                'name' => 'Universidad de Chile',
                'email' => 'compras@uchile.cl',
                'phone' => '+56 2 2978 2000',
                'address' => 'Av. Libertador Bernardo O\'Higgins 1058, Santiago'
            ],
            [
                'company_id' => 1,
                'name' => 'Retail S.A.',
                'email' => 'proveedores@retail.cl',
                'phone' => '+56 2 2590 4000',
                'address' => 'Av. Presidente Kennedy 9001, Las Condes'
            ],
            [
                'company_id' => 1,
                'name' => 'Hospital Las Condes',
                'email' => 'facturacion@hlc.cl',
                'phone' => '+56 2 2210 4000',
                'address' => 'Estoril 450, Las Condes'
            ],

            // Clientes de Constructora Andina Ltda (company_id: 2)
            [
                'company_id' => 2,
                'name' => 'Inmobiliaria Los Andes',
                'email' => 'proyectos@inmobiliarialosandes.cl',
                'phone' => '+56 2 2345 6789',
                'address' => 'Av. Las Condes 12345, Las Condes'
            ],
            [
                'company_id' => 2,
                'name' => 'Ministerio de Obras Públicas',
                'email' => 'licitaciones@mop.gov.cl',
                'phone' => '+56 2 2449 3000',
                'address' => 'Morandé 59, Santiago Centro'
            ],
            [
                'company_id' => 2,
                'name' => 'Condominio Alto Las Condes',
                'email' => 'administracion@altocondes.cl',
                'phone' => '+56 2 2234 5678',
                'address' => 'Av. Kennedy 4567, Las Condes'
            ],
            [
                'company_id' => 2,
                'name' => 'Mall Plaza del Sol',
                'email' => 'obras@mallplazadelsol.cl',
                'phone' => '+56 2 2876 5432',
                'address' => 'Av. Américo Vespucio 1001, Huechuraba'
            ],

            // Clientes de Comercial del Norte (company_id: 3)
            [
                'company_id' => 3,
                'name' => 'Minera Escondida',
                'email' => 'compras@escondida.cl',
                'phone' => '+56 55 230 1000',
                'address' => 'Av. de la Minería 501, Antofagasta'
            ],
            [
                'company_id' => 3,
                'name' => 'Puerto de Antofagasta',
                'email' => 'proveedores@puertantofagasta.cl',
                'phone' => '+56 55 264 5000',
                'address' => 'Terminal Portuario, Antofagasta'
            ],
            [
                'company_id' => 3,
                'name' => 'Supermercados del Norte',
                'email' => 'logistica@supernorte.cl',
                'phone' => '+56 55 245 6789',
                'address' => 'Av. Argentina 1234, Antofagasta'
            ],

            // Clientes de Servicios Gastronómicos Austral (company_id: 4)
            [
                'company_id' => 4,
                'name' => 'Hotel Cabo de Hornos',
                'email' => 'gerencia@cabodehornos.cl',
                'phone' => '+56 61 271 5000',
                'address' => 'Plaza Muñoz Gamero 1025, Punta Arenas'
            ],
            [
                'company_id' => 4,
                'name' => 'Casino Dreams Punta Arenas',
                'email' => 'administracion@dreamspuntaarenas.cl',
                'phone' => '+56 61 271 7777',
                'address' => 'Av. Colón 777, Punta Arenas'
            ],
            [
                'company_id' => 4,
                'name' => 'Cruceros Australis',
                'email' => 'operaciones@australis.com',
                'phone' => '+56 61 220 4500',
                'address' => 'Av. El Bosque Norte 0440, Las Condes'
            ],

            // Clientes de Innovación Digital S.A. (company_id: 5)
            [
                'company_id' => 5,
                'name' => 'Startup Chile',
                'email' => 'partnerships@startupchile.org',
                'phone' => '+56 2 2473 3000',
                'address' => 'Teatinos 280, Santiago Centro'
            ],
            [
                'company_id' => 5,
                'name' => 'Cornershop by Uber',
                'email' => 'procurement@cornershopapp.com',
                'phone' => '+56 2 2890 5000',
                'address' => 'Av. Providencia 1208, Providencia'
            ],
            [
                'company_id' => 5,
                'name' => 'Banco de Crédito e Inversiones',
                'email' => 'tecnologia@bci.cl',
                'phone' => '+56 2 2692 9000',
                'address' => 'Huérfanos 1134, Santiago Centro'
            ],
            [
                'company_id' => 5,
                'name' => 'SONDA S.A.',
                'email' => 'alianzas@sonda.com',
                'phone' => '+56 2 2657 5000',
                'address' => 'Teatinos 500, Santiago Centro'
            ]
        ];

        foreach ($clients as $client) {
            Client::create($client);
        }
    }
}
