<?php

namespace Database\Seeders;

use App\Models\Invoice;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $invoices = [
            // Facturas de TechSolutions SpA (company_id: 1)
            [
                'company_id' => 1,
                'client_id' => 1,
                'invoice_number' => 'TS-2024-001',
                'amount' => 2500000.00,
                'status' => 'paid',
                'due_date' => Carbon::now()->subDays(15),
                'issue_date' => Carbon::now()->subDays(45),
                'notes' => 'Desarrollo de sistema de gestión bancaria - Fase 1'
            ],
            [
                'company_id' => 1,
                'client_id' => 2,
                'invoice_number' => 'TS-2024-002',
                'amount' => 1800000.00,
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(15),
                'issue_date' => Carbon::now()->subDays(15),
                'notes' => 'Implementación plataforma e-learning universidad'
            ],
            [
                'company_id' => 1,
                'client_id' => 3,
                'invoice_number' => 'TS-2024-003',
                'amount' => 3200000.00,
                'status' => 'overdue',
                'due_date' => Carbon::now()->subDays(10),
                'issue_date' => Carbon::now()->subDays(40),
                'notes' => 'Sistema de inventario y POS para retail'
            ],
            [
                'company_id' => 1,
                'client_id' => 4,
                'invoice_number' => 'TS-2024-004',
                'amount' => 4500000.00,
                'status' => 'draft',
                'due_date' => Carbon::now()->addDays(30),
                'issue_date' => Carbon::now(),
                'notes' => 'Sistema de gestión hospitalaria integral'
            ],

            // Facturas de Constructora Andina Ltda (company_id: 2)
            [
                'company_id' => 2,
                'client_id' => 5,
                'invoice_number' => 'CA-2024-001',
                'amount' => 15000000.00,
                'status' => 'paid',
                'due_date' => Carbon::now()->subDays(5),
                'issue_date' => Carbon::now()->subDays(35),
                'notes' => 'Construcción edificio residencial - Etapa 1'
            ],
            [
                'company_id' => 2,
                'client_id' => 6,
                'invoice_number' => 'CA-2024-002',
                'amount' => 25000000.00,
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(20),
                'issue_date' => Carbon::now()->subDays(10),
                'notes' => 'Pavimentación carretera región metropolitana'
            ],
            [
                'company_id' => 2,
                'client_id' => 7,
                'invoice_number' => 'CA-2024-003',
                'amount' => 8500000.00,
                'status' => 'overdue',
                'due_date' => Carbon::now()->subDays(20),
                'issue_date' => Carbon::now()->subDays(50),
                'notes' => 'Remodelación áreas comunes condominio'
            ],
            [
                'company_id' => 2,
                'client_id' => 8,
                'invoice_number' => 'CA-2024-004',
                'amount' => 12000000.00,
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(25),
                'issue_date' => Carbon::now()->subDays(5),
                'notes' => 'Ampliación mall - nuevos locales comerciales'
            ],

            // Facturas de Comercial del Norte (company_id: 3)
            [
                'company_id' => 3,
                'client_id' => 9,
                'invoice_number' => 'CN-2024-001',
                'amount' => 5600000.00,
                'status' => 'paid',
                'due_date' => Carbon::now()->subDays(8),
                'issue_date' => Carbon::now()->subDays(38),
                'notes' => 'Suministro equipos mineros especializados'
            ],
            [
                'company_id' => 3,
                'client_id' => 10,
                'invoice_number' => 'CN-2024-002',
                'amount' => 3400000.00,
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(12),
                'issue_date' => Carbon::now()->subDays(18),
                'notes' => 'Servicios logísticos portuarios - Mes de agosto'
            ],
            [
                'company_id' => 3,
                'client_id' => 11,
                'invoice_number' => 'CN-2024-003',
                'amount' => 2100000.00,
                'status' => 'overdue',
                'due_date' => Carbon::now()->subDays(15),
                'issue_date' => Carbon::now()->subDays(45),
                'notes' => 'Distribución productos supermercados del norte'
            ],

            // Facturas de Servicios Gastronómicos Austral (company_id: 4)
            [
                'company_id' => 4,
                'client_id' => 12,
                'invoice_number' => 'SGA-2024-001',
                'amount' => 1200000.00,
                'status' => 'paid',
                'due_date' => Carbon::now()->subDays(12),
                'issue_date' => Carbon::now()->subDays(42),
                'notes' => 'Servicios de catering eventos corporativos'
            ],
            [
                'company_id' => 4,
                'client_id' => 13,
                'invoice_number' => 'SGA-2024-002',
                'amount' => 2800000.00,
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(18),
                'issue_date' => Carbon::now()->subDays(12),
                'notes' => 'Gestión restaurante casino - Mes de agosto'
            ],
            [
                'company_id' => 4,
                'client_id' => 14,
                'invoice_number' => 'SGA-2024-003',
                'amount' => 4500000.00,
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(22),
                'issue_date' => Carbon::now()->subDays(8),
                'notes' => 'Catering cruceros turísticos - Temporada alta'
            ],

            // Facturas de Innovación Digital S.A. (company_id: 5)
            [
                'company_id' => 5,
                'client_id' => 15,
                'invoice_number' => 'ID-2024-001',
                'amount' => 980000.00,
                'status' => 'paid',
                'due_date' => Carbon::now()->subDays(7),
                'issue_date' => Carbon::now()->subDays(37),
                'notes' => 'Desarrollo aplicación móvil para emprendedores'
            ],
            [
                'company_id' => 5,
                'client_id' => 16,
                'invoice_number' => 'ID-2024-002',
                'amount' => 1500000.00,
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(10),
                'issue_date' => Carbon::now()->subDays(20),
                'notes' => 'Integración API Cornershop - desarrollo custom'
            ],
            [
                'company_id' => 5,
                'client_id' => 17,
                'invoice_number' => 'ID-2024-003',
                'amount' => 2200000.00,
                'status' => 'overdue',
                'due_date' => Carbon::now()->subDays(5),
                'issue_date' => Carbon::now()->subDays(35),
                'notes' => 'Modernización sistema bancario core'
            ],
            [
                'company_id' => 5,
                'client_id' => 18,
                'invoice_number' => 'ID-2024-004',
                'amount' => 3800000.00,
                'status' => 'draft',
                'due_date' => Carbon::now()->addDays(30),
                'issue_date' => Carbon::now(),
                'notes' => 'Consultoría digital transformation SONDA'
            ]
        ];

        foreach ($invoices as $invoice) {
            Invoice::create($invoice);
        }
    }
}
