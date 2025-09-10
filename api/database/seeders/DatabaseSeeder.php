<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ejecutar seeders en orden correcto (respetando relaciones)
        $this->call([
            CompanySeeder::class,
            UserSeeder::class,
            ClientSeeder::class,
            InvoiceSeeder::class,
            InvoiceItemSeeder::class,
            PaymentSeeder::class,
            QuoteSeeder::class,
            QuoteItemSeeder::class,
            ScheduleSeeder::class,
        ]);

        $this->command->info('✅ Base de datos poblada con datos de prueba exitosamente!');
        $this->command->info('📊 Datos creados:');
        $this->command->info('   • 5 Empresas con usuarios y datos completos');
        $this->command->info('   • 18 Clientes distribuidos entre las empresas');
        $this->command->info('   • 18 Facturas con diferentes estados');
        $this->command->info('   • 16 Pagos (completados, parciales y fallidos)');
        $this->command->info('   • 19 Cotizaciones con varios estados');
        $this->command->info('   • Items detallados para facturas y cotizaciones');
        $this->command->info('   • 10 Tareas programadas de automatización');
        $this->command->info('');
        $this->command->info('🔑 Usuarios de prueba:');
        $this->command->info('   • Admin: admin@factufast.cl / password123');
        $this->command->info('   • TechSolutions: carlos@techsolutions.cl / password123');
        $this->command->info('   • Constructora: roberto@constructoraandina.cl / password123');
        $this->command->info('   • Comercial Norte: pedro@comercialnorte.cl / password123');
        $this->command->info('   • Gastronómica: sofia@gastronomicaustral.cl / password123');
        $this->command->info('   • Innovación: diego@innovaciondigital.cl / password123');
    }
}
