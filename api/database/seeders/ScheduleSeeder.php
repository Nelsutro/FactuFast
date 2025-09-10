<?php

namespace Database\Seeders;

use App\Models\Schedule;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = [
            // Recordatorios automáticos de facturas
            [
                'company_id' => 1,
                'name' => 'Recordatorio facturas vencidas',
                'type' => 'invoice_reminder',
                'frequency' => 'daily',
                'time' => '09:00:00',
                'is_active' => true,
                'last_run' => Carbon::now()->subDays(1),
                'next_run' => Carbon::now()->addDay(),
                'description' => 'Envío automático de recordatorios para facturas vencidas',
                'settings' => json_encode([
                    'days_before' => 3,
                    'days_after' => [1, 7, 15],
                    'email_template' => 'invoice_reminder'
                ])
            ],
            [
                'company_id' => 2,
                'name' => 'Recordatorio facturas próximas a vencer',
                'type' => 'invoice_reminder',
                'frequency' => 'daily',
                'time' => '08:30:00',
                'is_active' => true,
                'last_run' => Carbon::now()->subDays(1),
                'next_run' => Carbon::now()->addDay(),
                'description' => 'Notificación 3 días antes del vencimiento',
                'settings' => json_encode([
                    'days_before' => 3,
                    'email_template' => 'invoice_due_soon'
                ])
            ],

            // Generación automática de reportes
            [
                'company_id' => 1,
                'name' => 'Reporte mensual de ventas',
                'type' => 'report_generation',
                'frequency' => 'monthly',
                'time' => '07:00:00',
                'is_active' => true,
                'last_run' => Carbon::now()->subMonth(),
                'next_run' => Carbon::now()->addMonth()->startOfMonth(),
                'description' => 'Generación automática de reporte mensual de ventas',
                'settings' => json_encode([
                    'report_type' => 'sales_summary',
                    'recipients' => ['carlos@techsolutions.cl', 'ana@techsolutions.cl'],
                    'format' => 'pdf'
                ])
            ],
            [
                'company_id' => 3,
                'name' => 'Reporte semanal de pagos',
                'type' => 'report_generation',
                'frequency' => 'weekly',
                'time' => '08:00:00',
                'is_active' => true,
                'last_run' => Carbon::now()->subWeek(),
                'next_run' => Carbon::now()->addWeek()->startOfWeek(),
                'description' => 'Reporte semanal del estado de pagos',
                'settings' => json_encode([
                    'report_type' => 'payment_status',
                    'recipients' => ['pedro@comercialnorte.cl'],
                    'format' => 'excel'
                ])
            ],

            // Seguimiento de cotizaciones
            [
                'company_id' => 2,
                'name' => 'Seguimiento cotizaciones pendientes',
                'type' => 'quote_followup',
                'frequency' => 'weekly',
                'time' => '10:00:00',
                'is_active' => true,
                'last_run' => Carbon::now()->subWeek(),
                'next_run' => Carbon::now()->addWeek(),
                'description' => 'Seguimiento semanal de cotizaciones enviadas sin respuesta',
                'settings' => json_encode([
                    'days_after_sent' => 7,
                    'follow_up_template' => 'quote_followup',
                    'max_followups' => 3
                ])
            ],
            [
                'company_id' => 5,
                'name' => 'Alerta cotizaciones próximas a expirar',
                'type' => 'quote_followup',
                'frequency' => 'daily',
                'time' => '09:30:00',
                'is_active' => true,
                'last_run' => Carbon::now()->subDay(),
                'next_run' => Carbon::now()->addDay(),
                'description' => 'Alertas para cotizaciones que expiran en 3 días',
                'settings' => json_encode([
                    'days_before_expiry' => 3,
                    'alert_recipients' => ['diego@innovaciondigital.cl']
                ])
            ],

            // Backup automático
            [
                'company_id' => 1,
                'name' => 'Backup diario de datos',
                'type' => 'backup',
                'frequency' => 'daily',
                'time' => '02:00:00',
                'is_active' => true,
                'last_run' => Carbon::now()->subDay(),
                'next_run' => Carbon::now()->addDay(),
                'description' => 'Backup automático diario de todos los datos',
                'settings' => json_encode([
                    'backup_type' => 'full',
                    'retention_days' => 30,
                    'storage_location' => 'cloud'
                ])
            ],

            // Notificaciones de pagos
            [
                'company_id' => 4,
                'name' => 'Confirmación automática de pagos',
                'type' => 'payment_followup',
                'frequency' => 'daily',
                'time' => null,
                'is_active' => true,
                'last_run' => Carbon::now()->subHours(2),
                'next_run' => null,
                'description' => 'Envío inmediato de confirmación cuando se recibe un pago',
                'settings' => json_encode([
                    'confirmation_template' => 'payment_received',
                    'include_receipt' => true
                ])
            ],

            // Tareas de mantenimiento
            [
                'company_id' => 1, // Empresa TechSolutions SpA como administrador del sistema
                'name' => 'Limpieza logs del sistema',
                'type' => 'backup',
                'frequency' => 'weekly',
                'time' => '03:00:00',
                'is_active' => true,
                'last_run' => Carbon::now()->subWeek(),
                'next_run' => Carbon::now()->addWeek(),
                'description' => 'Limpieza automática de logs antiguos del sistema',
                'settings' => json_encode([
                    'log_retention_days' => 90,
                    'cleanup_tables' => ['logs', 'notifications', 'temporary_files']
                ])
            ],

            // Recordatorios personalizados
            [
                'company_id' => 5,
                'name' => 'Revisión mensual de clientes inactivos',
                'type' => 'report_generation',
                'frequency' => 'monthly',
                'time' => '10:00:00',
                'is_active' => true,
                'last_run' => Carbon::now()->subMonth(),
                'next_run' => Carbon::now()->addMonth()->startOfMonth(),
                'description' => 'Revisión mensual de clientes sin actividad reciente',
                'settings' => json_encode([
                    'inactive_days_threshold' => 90,
                    'report_recipients' => ['valentina@innovaciondigital.cl']
                ])
            ]
        ];

        foreach ($schedules as $schedule) {
            Schedule::create($schedule);
        }
    }
}
