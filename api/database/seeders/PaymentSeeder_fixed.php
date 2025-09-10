<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $payments = [
            // Pagos para facturas pagadas
            [
                'invoice_id' => 1, // TS-2024-001
                'company_id' => 1,
                'client_id' => 1,
                'amount' => 2500000.00,
                'payment_method' => 'bank_transfer',
                'transaction_id' => 'TRF-20240815-001',
                'status' => 'completed',
                'payment_date' => Carbon::now()->subDays(30),
                'notes' => 'Transferencia bancaria - Proyecto completado satisfactoriamente'
            ],
            [
                'invoice_id' => 5, // CA-2024-001
                'company_id' => 2,
                'client_id' => 8,
                'amount' => 15000000.00,
                'payment_method' => 'bank_transfer',
                'transaction_id' => 'TRF-20240820-002',
                'status' => 'completed',
                'payment_date' => Carbon::now()->subDays(25),
                'notes' => 'Pago por construcción edificio - Etapa 1 finalizada'
            ],
            [
                'invoice_id' => 9, // CN-2024-001
                'company_id' => 3,
                'client_id' => 12,
                'amount' => 5600000.00,
                'payment_method' => 'credit_card',
                'transaction_id' => 'CC-20240822-003',
                'status' => 'completed',
                'payment_date' => Carbon::now()->subDays(23),
                'notes' => 'Pago con tarjeta corporativa - Equipos entregados'
            ],
            [
                'invoice_id' => 12, // SGA-2024-001
                'company_id' => 4,
                'client_id' => 15,
                'amount' => 1200000.00,
                'payment_method' => 'bank_transfer',
                'transaction_id' => 'TRF-20240825-004',
                'status' => 'completed',
                'payment_date' => Carbon::now()->subDays(20),
                'notes' => 'Servicios de catering - Eventos realizados exitosamente'
            ],
            [
                'invoice_id' => 15, // ID-2024-001
                'company_id' => 5,
                'client_id' => 17,
                'amount' => 980000.00,
                'payment_method' => 'bank_transfer',
                'transaction_id' => 'TRF-20240828-005',
                'status' => 'completed',
                'payment_date' => Carbon::now()->subDays(17),
                'notes' => 'App móvil entregada y aprobada por Startup Chile'
            ],

            // Pagos parciales para facturas pendientes
            [
                'invoice_id' => 2, // TS-2024-002 (pending)
                'company_id' => 1,
                'client_id' => 2,
                'amount' => 900000.00,
                'payment_method' => 'bank_transfer',
                'transaction_id' => 'TRF-20240901-006',
                'status' => 'completed',
                'payment_date' => Carbon::now()->subDays(10),
                'notes' => 'Pago parcial 50% - Plataforma e-learning en desarrollo'
            ],
            [
                'invoice_id' => 6, // CA-2024-002 (pending)
                'company_id' => 2,
                'client_id' => 9,
                'amount' => 12500000.00,
                'payment_method' => 'bank_transfer',
                'transaction_id' => 'TRF-20240903-007',
                'status' => 'completed',
                'payment_date' => Carbon::now()->subDays(8),
                'notes' => 'Anticipo 50% - Pavimentación carretera iniciada'
            ],
            [
                'invoice_id' => 8, // CA-2024-004 (pending)
                'company_id' => 2,
                'client_id' => 11,
                'amount' => 6000000.00,
                'payment_method' => 'credit_card',
                'transaction_id' => 'CC-20240905-008',
                'status' => 'completed',
                'payment_date' => Carbon::now()->subDays(6),
                'notes' => 'Anticipo 60% - Construcción casa iniciada'
            ],
            [
                'invoice_id' => 13, // SGA-2024-002 (pending)
                'company_id' => 4,
                'client_id' => 16,
                'amount' => 850000.00,
                'payment_method' => 'cash',
                'transaction_id' => null,
                'status' => 'completed',
                'payment_date' => Carbon::now()->subDays(4),
                'notes' => 'Pago en efectivo - Consultoría inicial completada'
            ],

            // Pagos fallidos o en proceso
            [
                'invoice_id' => 3, // TS-2024-003 (overdue)
                'company_id' => 1,
                'client_id' => 3,
                'amount' => 3200000.00,
                'payment_method' => 'bank_transfer',
                'transaction_id' => 'TRF-20240907-009',
                'status' => 'failed',
                'payment_date' => Carbon::now()->subDays(3),
                'notes' => 'Transferencia rechazada - Fondos insuficientes'
            ],
            [
                'invoice_id' => 7, // CA-2024-003 (overdue)
                'company_id' => 2,
                'client_id' => 10,
                'amount' => 8500000.00,
                'payment_method' => 'credit_card',
                'transaction_id' => 'CC-20240908-010',
                'status' => 'pending',
                'payment_date' => Carbon::now()->subDays(2),
                'notes' => 'Pago en proceso - Validando con banco'
            ],
            [
                'invoice_id' => 11, // CN-2024-003 (overdue)
                'company_id' => 3,
                'client_id' => 14,
                'amount' => 4200000.00,
                'payment_method' => 'bank_transfer',
                'transaction_id' => 'TRF-20240909-011',
                'status' => 'failed',
                'payment_date' => Carbon::now()->subDay(),
                'notes' => 'Error en transferencia - Datos bancarios incorrectos'
            ],
            [
                'invoice_id' => 14, // SGA-2024-003 (overdue)
                'company_id' => 4,
                'client_id' => 15,
                'amount' => 1500000.00,
                'payment_method' => 'credit_card',
                'transaction_id' => 'CC-20240910-012',
                'status' => 'pending',
                'payment_date' => Carbon::now(),
                'notes' => 'Procesando pago - Autorización pendiente'
            ],

            // Pagos adicionales para facturas con múltiples transacciones
            [
                'invoice_id' => 16, // ID-2024-002 (paid)
                'company_id' => 5,
                'client_id' => 18,
                'amount' => 750000.00,
                'payment_method' => 'bank_transfer',
                'transaction_id' => 'TRF-20240825-013',
                'status' => 'completed',
                'payment_date' => Carbon::now()->subDays(16),
                'notes' => 'Primer pago - Sistema de gestión fase 1'
            ],
            [
                'invoice_id' => 16, // ID-2024-002 (paid) - segundo pago
                'company_id' => 5,
                'client_id' => 18,
                'amount' => 750000.00,
                'payment_method' => 'bank_transfer',
                'transaction_id' => 'TRF-20240901-014',
                'status' => 'completed',
                'payment_date' => Carbon::now()->subDays(9),
                'notes' => 'Segundo pago - Sistema de gestión completado'
            ],
            [
                'invoice_id' => 17, // ID-2024-003 (pending)
                'company_id' => 5,
                'client_id' => 1,
                'amount' => 1100000.00,
                'payment_method' => 'credit_card',
                'transaction_id' => 'CC-20240905-015',
                'status' => 'completed',
                'payment_date' => Carbon::now()->subDays(5),
                'notes' => 'Pago parcial 55% - Mantenimiento web en progreso'
            ]
        ];

        foreach ($payments as $payment) {
            Payment::create($payment);
        }
    }
}
