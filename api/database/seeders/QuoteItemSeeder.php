<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuoteItem;

class QuoteItemSeeder extends Seeder
{
    public function run(): void
    {
        $quoteItems = [
            // Items para cotización QTS-2024-001 (quote_id: 1)
            [
                'quote_id' => 1,
                'description' => 'Consultoría sistema inventario',
                'quantity' => 20,
                'unit_price' => 85000.00,
                'amount' => 1700000.00
            ],
            [
                'quote_id' => 1,
                'description' => 'Desarrollo prototipo',
                'quantity' => 40,
                'unit_price' => 65000.00,
                'amount' => 2600000.00
            ],

            // Items para cotización QCA-2024-001 (quote_id: 2)
            [
                'quote_id' => 2,
                'description' => 'Remodelación oficinas principales',
                'quantity' => 1,
                'unit_price' => 18500000.00,
                'amount' => 18500000.00
            ],
            [
                'quote_id' => 2,
                'description' => 'Diseño arquitectónico',
                'quantity' => 30,
                'unit_price' => 75000.00,
                'amount' => 2250000.00
            ],

            // Items para cotización QCN-2024-001 (quote_id: 3)
            [
                'quote_id' => 3,
                'description' => 'Workstations Dell Precision',
                'quantity' => 25,
                'unit_price' => 850000.00,
                'amount' => 21250000.00
            ],
            [
                'quote_id' => 3,
                'description' => 'Instalación y configuración',
                'quantity' => 25,
                'unit_price' => 45000.00,
                'amount' => 1125000.00
            ],

            // Items para cotización QSGA-2024-001 (quote_id: 4)
            [
                'quote_id' => 4,
                'description' => 'Catering premium para 300 personas',
                'quantity' => 300,
                'unit_price' => 15000.00,
                'amount' => 4500000.00
            ],
            [
                'quote_id' => 4,
                'description' => 'Decoración y ambientación',
                'quantity' => 1,
                'unit_price' => 2800000.00,
                'amount' => 2800000.00
            ],

            // Items para cotización QID-2024-001 (quote_id: 5)
            [
                'quote_id' => 5,
                'description' => 'App web progresiva (PWA)',
                'quantity' => 50,
                'unit_price' => 35000.00,
                'amount' => 1750000.00
            ],
            [
                'quote_id' => 5,
                'description' => 'Integración APIs externas',
                'quantity' => 20,
                'unit_price' => 42000.00,
                'amount' => 840000.00
            ],

            // Items para cotización QTS-2024-002 (quote_id: 6)
            [
                'quote_id' => 6,
                'description' => 'Sistema CRM empresarial',
                'quantity' => 60,
                'unit_price' => 55000.00,
                'amount' => 3300000.00
            ],
            [
                'quote_id' => 6,
                'description' => 'Migración de datos',
                'quantity' => 25,
                'unit_price' => 48000.00,
                'amount' => 1200000.00
            ],

            // Items para cotización QCA-2024-002 (quote_id: 7)
            [
                'quote_id' => 7,
                'description' => 'Construcción puente peatonal',
                'quantity' => 1,
                'unit_price' => 45000000.00,
                'amount' => 45000000.00
            ],
            [
                'quote_id' => 7,
                'description' => 'Estudios de impacto ambiental',
                'quantity' => 1,
                'unit_price' => 8500000.00,
                'amount' => 8500000.00
            ],

            // Items para cotización QCN-2024-002 (quote_id: 8)
            [
                'quote_id' => 8,
                'description' => 'Servidores HP ProLiant',
                'quantity' => 5,
                'unit_price' => 3200000.00,
                'amount' => 16000000.00
            ],
            [
                'quote_id' => 8,
                'description' => 'Configuración datacenter',
                'quantity' => 80,
                'unit_price' => 65000.00,
                'amount' => 5200000.00
            ],

            // Items para cotización QSGA-2024-002 (quote_id: 9)
            [
                'quote_id' => 9,
                'description' => 'Auditoría sistemas calidad',
                'quantity' => 40,
                'unit_price' => 95000.00,
                'amount' => 3800000.00
            ],
            [
                'quote_id' => 9,
                'description' => 'Implementación ISO 9001',
                'quantity' => 60,
                'unit_price' => 85000.00,
                'amount' => 5100000.00
            ],

            // Items para cotización QID-2024-002 (quote_id: 10)
            [
                'quote_id' => 10,
                'description' => 'Plataforma e-commerce',
                'quantity' => 90,
                'unit_price' => 38000.00,
                'amount' => 3420000.00
            ],
            [
                'quote_id' => 10,
                'description' => 'Pasarela de pagos',
                'quantity' => 25,
                'unit_price' => 72000.00,
                'amount' => 1800000.00
            ],

            // Items para cotización QTS-2024-003 (quote_id: 11)
            [
                'quote_id' => 11,
                'description' => 'Migración a la nube AWS',
                'quantity' => 45,
                'unit_price' => 75000.00,
                'amount' => 3375000.00
            ],

            // Items para cotización QCA-2024-003 (quote_id: 12)
            [
                'quote_id' => 12,
                'description' => 'Reparación estructura antigua',
                'quantity' => 1,
                'unit_price' => 22000000.00,
                'amount' => 22000000.00
            ],

            // Items para cotización QCN-2024-003 (quote_id: 13)
            [
                'quote_id' => 13,
                'description' => 'Tablets Samsung Galaxy',
                'quantity' => 50,
                'unit_price' => 320000.00,
                'amount' => 16000000.00
            ],

            // Items para cotización QSGA-2024-003 (quote_id: 14)
            [
                'quote_id' => 14,
                'description' => 'Catering para conferencia',
                'quantity' => 500,
                'unit_price' => 12000.00,
                'amount' => 6000000.00
            ],

            // Items para cotización QID-2024-003 (quote_id: 15)
            [
                'quote_id' => 15,
                'description' => 'Sistema gestión hospitalaria',
                'quantity' => 120,
                'unit_price' => 65000.00,
                'amount' => 7800000.00
            ],

            // Items para cotización QTS-2024-004 (quote_id: 16)
            [
                'quote_id' => 16,
                'description' => 'Desarrollo API REST',
                'quantity' => 35,
                'unit_price' => 58000.00,
                'amount' => 2030000.00
            ],

            // Items para cotización QCA-2024-004 (quote_id: 17)
            [
                'quote_id' => 17,
                'description' => 'Construcción muro contención',
                'quantity' => 200,
                'unit_price' => 85000.00,
                'amount' => 17000000.00
            ],

            // Items para cotización QID-2024-004 (quote_id: 18)
            [
                'quote_id' => 18,
                'description' => 'Dashboard analytics avanzado',
                'quantity' => 40,
                'unit_price' => 62000.00,
                'amount' => 2480000.00
            ]
        ];

        foreach ($quoteItems as $item) {
            QuoteItem::create($item);
        }
    }
}
