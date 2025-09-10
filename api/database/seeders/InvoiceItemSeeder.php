<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InvoiceItem;

class InvoiceItemSeeder extends Seeder
{
    public function run(): void
    {
        $invoiceItems = [
            // Items para TS-2024-001 (invoice_id: 1) - Factura pagada
            [
                'invoice_id' => 1,
                'description' => 'Análisis y diseño del sistema',
                'quantity' => 40.00,
                'unit_price' => 35000.00,
                'amount' => 1400000.00
            ],
            [
                'invoice_id' => 1,
                'description' => 'Desarrollo módulos core',
                'quantity' => 60.00,
                'unit_price' => 45000.00,
                'amount' => 2700000.00
            ],
            [
                'invoice_id' => 1,
                'description' => 'Testing y documentación',
                'quantity' => 20.00,
                'unit_price' => 20000.00,
                'amount' => 400000.00
            ],

            // Items para TS-2024-002 (invoice_id: 2) - Factura pendiente
            [
                'invoice_id' => 2,
                'description' => 'Desarrollo plataforma LMS',
                'quantity' => 80.00,
                'unit_price' => 40000.00,
                'amount' => 3200000.00
            ],
            [
                'invoice_id' => 2,
                'description' => 'Integración APIs educativas',
                'quantity' => 30.00,
                'unit_price' => 50000.00,
                'amount' => 1500000.00
            ],

            // Items para TS-2024-003 (invoice_id: 3) - Factura vencida
            [
                'invoice_id' => 3,
                'description' => 'Sistema gestión inventarios',
                'quantity' => 70.00,
                'unit_price' => 50000.00,
                'amount' => 3500000.00
            ],

            // Items para CA-2024-001 (invoice_id: 5) - Factura pagada
            [
                'invoice_id' => 5,
                'description' => 'Materiales construcción edificio',
                'quantity' => 1.00,
                'unit_price' => 8500000.00,
                'amount' => 8500000.00
            ],
            [
                'invoice_id' => 5,
                'description' => 'Mano de obra especializada',
                'quantity' => 120.00,
                'unit_price' => 45000.00,
                'amount' => 5400000.00
            ],
            [
                'invoice_id' => 5,
                'description' => 'Supervisión técnica',
                'quantity' => 60.00,
                'unit_price' => 35000.00,
                'amount' => 2100000.00
            ],

            // Items para CA-2024-002 (invoice_id: 6) - Factura pendiente
            [
                'invoice_id' => 6,
                'description' => 'Pavimentación carretera 10km',
                'quantity' => 10.00,
                'unit_price' => 2500000.00,
                'amount' => 25000000.00
            ],

            // Items para CN-2024-001 (invoice_id: 9) - Factura pagada
            [
                'invoice_id' => 9,
                'description' => 'Laptops HP ProBook 450',
                'quantity' => 10.00,
                'unit_price' => 450000.00,
                'amount' => 4500000.00
            ],
            [
                'invoice_id' => 9,
                'description' => 'Licencias software Office 365',
                'quantity' => 10.00,
                'unit_price' => 120000.00,
                'amount' => 1200000.00
            ],

            // Items para CN-2024-002 (invoice_id: 10) - Factura pendiente
            [
                'invoice_id' => 10,
                'description' => 'Servidores Dell PowerEdge',
                'quantity' => 8.00,
                'unit_price' => 1200000.00,
                'amount' => 9600000.00
            ],

            // Items para SGA-2024-001 (invoice_id: 12) - Factura pagada
            [
                'invoice_id' => 12,
                'description' => 'Servicio catering evento corporativo',
                'quantity' => 150.00,
                'unit_price' => 8000.00,
                'amount' => 1200000.00
            ],

            // Items para ID-2024-001 (invoice_id: 15) - Factura pagada
            [
                'invoice_id' => 15,
                'description' => 'Desarrollo app móvil React Native',
                'quantity' => 35.00,
                'unit_price' => 28000.00,
                'amount' => 980000.00
            ],

            // Items para ID-2024-002 (invoice_id: 16) - Factura pagada
            [
                'invoice_id' => 16,
                'description' => 'Sistema gestión de inventarios',
                'quantity' => 50.00,
                'unit_price' => 30000.00,
                'amount' => 1500000.00
            ],

            // Items para ID-2024-003 (invoice_id: 17) - Factura pendiente
            [
                'invoice_id' => 17,
                'description' => 'Mantenimiento sitio web',
                'quantity' => 24.00,
                'unit_price' => 85000.00,
                'amount' => 2040000.00
            ],

            // Items para CA-2024-003 (invoice_id: 7) - Factura vencida
            [
                'invoice_id' => 7,
                'description' => 'Reparación infraestructura vial',
                'quantity' => 5.00,
                'unit_price' => 1700000.00,
                'amount' => 8500000.00
            ],

            // Items para CA-2024-004 (invoice_id: 8) - Factura pendiente
            [
                'invoice_id' => 8,
                'description' => 'Construcción casa unifamiliar',
                'quantity' => 1.00,
                'unit_price' => 10000000.00,
                'amount' => 10000000.00
            ],

            // Items para CN-2024-003 (invoice_id: 11) - Factura vencida
            [
                'invoice_id' => 11,
                'description' => 'Equipos de red Cisco',
                'quantity' => 15.00,
                'unit_price' => 280000.00,
                'amount' => 4200000.00
            ],

            // Items para SGA-2024-002 (invoice_id: 13) - Factura pendiente
            [
                'invoice_id' => 13,
                'description' => 'Consultoría procesos de calidad',
                'quantity' => 40.00,
                'unit_price' => 45000.00,
                'amount' => 1800000.00
            ],

            // Items para SGA-2024-003 (invoice_id: 14) - Factura vencida
            [
                'invoice_id' => 14,
                'description' => 'Auditoría sistemas de gestión',
                'quantity' => 30.00,
                'unit_price' => 50000.00,
                'amount' => 1500000.00
            ],

            // Items para TS-2024-004 (invoice_id: 4) - Factura pendiente
            [
                'invoice_id' => 4,
                'description' => 'Portal web corporativo',
                'quantity' => 45.00,
                'unit_price' => 55000.00,
                'amount' => 2475000.00
            ],

            // Items para TS-2024-005 (invoice_id: 18) - Factura pendiente
            [
                'invoice_id' => 18,
                'description' => 'Sistema de reportes avanzados',
                'quantity' => 60.00,
                'unit_price' => 48000.00,
                'amount' => 2880000.00
            ]
        ];

        foreach ($invoiceItems as $item) {
            InvoiceItem::create($item);
        }
    }
}
