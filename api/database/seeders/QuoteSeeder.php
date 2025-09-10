<?php

namespace Database\Seeders;

use App\Models\Quote;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class QuoteSeeder extends Seeder
{
    public function run(): void
    {
        $quotes = [
            // Cotizaciones de TechSolutions SpA (company_id: 1)
            [
                'company_id' => 1,
                'client_id' => 1,
                'quote_number' => 'QT-TS-2024-001',
                'amount' => 5200000.00,
                'status' => 'sent',
                'valid_until' => Carbon::now()->addDays(15),
                'created_at' => Carbon::now()->subDays(5),
                'notes' => 'Sistema de gestión bancaria - Fase 2 y 3',
                'notes' => 'Incluye módulos de reportería avanzada y dashboard ejecutivo'
            ],
            [
                'company_id' => 1,
                'client_id' => 2,
                'quote_number' => 'QT-TS-2024-002',
                'amount' => 3200000.00,
                'status' => 'accepted',
                'valid_until' => Carbon::now()->addDays(30),
                'created_at' => Carbon::now()->subDays(10),
                'notes' => 'Módulo de evaluación automática para e-learning',
                'notes' => 'Sistema de calificación con IA y análisis de rendimiento'
            ],
            [
                'company_id' => 1,
                'client_id' => 3,
                'quote_number' => 'QT-TS-2024-003',
                'amount' => 2800000.00,
                'status' => 'expired',
                'valid_until' => Carbon::now()->subDays(5),
                'created_at' => Carbon::now()->subDays(35),
                'notes' => 'Actualización sistema POS con nuevas funcionalidades',
                'notes' => 'Integración con sistemas de fidelización y marketing'
            ],
            [
                'company_id' => 1,
                'client_id' => 4,
                'quote_number' => 'QT-TS-2024-004',
                'amount' => 6800000.00,
                'status' => 'draft',
                'valid_until' => Carbon::now()->addDays(45),
                'created_at' => Carbon::now(),
                'notes' => 'Sistema de telemedicina y consultas remotas',
                'notes' => 'Plataforma completa con videoconferencia y historial médico'
            ],

            // Cotizaciones de Constructora Andina Ltda (company_id: 2)
            [
                'company_id' => 2,
                'client_id' => 5,
                'quote_number' => 'QT-CA-2024-001',
                'amount' => 28000000.00,
                'status' => 'sent',
                'valid_until' => Carbon::now()->addDays(20),
                'created_at' => Carbon::now()->subDays(7),
                'notes' => 'Construcción edificio residencial - Etapa 2',
                'notes' => 'Incluye acabados premium y áreas verdes'
            ],
            [
                'company_id' => 2,
                'client_id' => 6,
                'quote_number' => 'QT-CA-2024-002',
                'amount' => 45000000.00,
                'status' => 'accepted',
                'valid_until' => Carbon::now()->addDays(60),
                'created_at' => Carbon::now()->subDays(15),
                'notes' => 'Construcción puente peatonal región metropolitana',
                'notes' => 'Estructura metálica con diseño arquitectónico moderno'
            ],
            [
                'company_id' => 2,
                'client_id' => 7,
                'quote_number' => 'QT-CA-2024-003',
                'amount' => 12000000.00,
                'status' => 'rejected',
                'valid_until' => Carbon::now()->subDays(10),
                'created_at' => Carbon::now()->subDays(40),
                'notes' => 'Renovación completa lobby y accesos condominio',
                'notes' => 'Cliente optó por otra empresa constructora'
            ],
            [
                'company_id' => 2,
                'client_id' => 8,
                'quote_number' => 'QT-CA-2024-004',
                'amount' => 18500000.00,
                'status' => 'sent',
                'valid_until' => Carbon::now()->addDays(25),
                'created_at' => Carbon::now()->subDays(3),
                'notes' => 'Construcción food court y zona de entretenimiento',
                'notes' => 'Diseño innovador con espacios multifuncionales'
            ],

            // Cotizaciones de Comercial del Norte (company_id: 3)
            [
                'company_id' => 3,
                'client_id' => 9,
                'quote_number' => 'QT-CN-2024-001',
                'amount' => 8200000.00,
                'status' => 'sent',
                'valid_until' => Carbon::now()->addDays(12),
                'created_at' => Carbon::now()->subDays(8),
                'notes' => 'Suministro maquinaria pesada para minería',
                'notes' => 'Incluye mantenimiento por 2 años y capacitación'
            ],
            [
                'company_id' => 3,
                'client_id' => 10,
                'quote_number' => 'QT-CN-2024-002',
                'amount' => 4500000.00,
                'status' => 'accepted',
                'valid_until' => Carbon::now()->addDays(18),
                'created_at' => Carbon::now()->subDays(12),
                'notes' => 'Servicios logísticos especializados - Contrato anual',
                'notes' => 'Operación 24/7 con seguimiento GPS y reportería'
            ],
            [
                'company_id' => 3,
                'client_id' => 11,
                'quote_number' => 'QT-CN-2024-003',
                'amount' => 3600000.00,
                'status' => 'expired',
                'valid_until' => Carbon::now()->subDays(8),
                'created_at' => Carbon::now()->subDays(38),
                'notes' => 'Distribución y almacenamiento productos refrigerados',
                'notes' => 'Cliente requirió modificaciones en la propuesta'
            ],

            // Cotizaciones de Servicios Gastronómicos Austral (company_id: 4)
            [
                'company_id' => 4,
                'client_id' => 12,
                'quote_number' => 'QT-SGA-2024-001',
                'amount' => 2400000.00,
                'status' => 'sent',
                'valid_until' => Carbon::now()->addDays(10),
                'created_at' => Carbon::now()->subDays(6),
                'notes' => 'Catering premium para temporada de congresos',
                'notes' => 'Menús gourmet con productos locales patagónicos'
            ],
            [
                'company_id' => 4,
                'client_id' => 13,
                'quote_number' => 'QT-SGA-2024-002',
                'amount' => 5200000.00,
                'status' => 'accepted',
                'valid_until' => Carbon::now()->addDays(30),
                'created_at' => Carbon::now()->subDays(14),
                'notes' => 'Gestión integral restaurante casino - Renovación',
                'notes' => 'Incluye renovación de menú y capacitación de personal'
            ],
            [
                'company_id' => 4,
                'client_id' => 14,
                'quote_number' => 'QT-SGA-2024-003',
                'amount' => 8500000.00,
                'status' => 'sent',
                'valid_until' => Carbon::now()->addDays(22),
                'created_at' => Carbon::now()->subDays(4),
                'notes' => 'Catering cruceros temporada 2024-2025',
                'notes' => 'Servicio completo para 200 pasajeros por crucero'
            ],

            // Cotizaciones de Innovación Digital S.A. (company_id: 5)
            [
                'company_id' => 5,
                'client_id' => 15,
                'quote_number' => 'QT-ID-2024-001',
                'amount' => 1800000.00,
                'status' => 'sent',
                'valid_until' => Carbon::now()->addDays(14),
                'created_at' => Carbon::now()->subDays(9),
                'notes' => 'Dashboard analítico para seguimiento startups',
                'notes' => 'Integración con APIs de múltiples plataformas'
            ],
            [
                'company_id' => 5,
                'client_id' => 16,
                'quote_number' => 'QT-ID-2024-002',
                'amount' => 2800000.00,
                'status' => 'accepted',
                'valid_until' => Carbon::now()->addDays(35),
                'created_at' => Carbon::now()->subDays(11),
                'notes' => 'Sistema de recomendaciones con machine learning',
                'notes' => 'IA avanzada para optimizar entregas Cornershop'
            ],
            [
                'company_id' => 5,
                'client_id' => 17,
                'quote_number' => 'QT-ID-2024-003',
                'amount' => 4200000.00,
                'status' => 'rejected',
                'valid_until' => Carbon::now()->subDays(3),
                'created_at' => Carbon::now()->subDays(33),
                'notes' => 'Migración completa a arquitectura cloud',
                'notes' => 'Cliente decidió desarrollar internamente'
            ],
            [
                'company_id' => 5,
                'client_id' => 18,
                'quote_number' => 'QT-ID-2024-004',
                'amount' => 6500000.00,
                'status' => 'draft',
                'valid_until' => Carbon::now()->addDays(40),
                'created_at' => Carbon::now(),
                'notes' => 'Transformación digital integral SONDA',
                'notes' => 'Proyecto piloto para expansión regional'
            ]
        ];

        foreach ($quotes as $quote) {
            Quote::create($quote);
        }
    }
}
