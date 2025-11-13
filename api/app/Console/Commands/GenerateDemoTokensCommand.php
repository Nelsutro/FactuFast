<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;

class GenerateDemoTokensCommand extends Command
{
    protected $signature = 'demo:tokens {--regenerate : Forzar regeneración de tokens existentes}';
    protected $description = 'Generar tokens de acceso demo para clientes existentes del portal';

    public function handle()
    {
        $this->info('🔑 Generando tokens de acceso demo para portal de clientes...');
        $this->line('');

        try {
            // Verificar que existan clientes
            $clientCount = Client::count();
            if ($clientCount === 0) {
                $this->error('❌ No hay clientes en la base de datos.');
                $this->line('Ejecuta primero: php artisan db:seed --class=ClientSeeder');
                return 1;
            }

            $this->info("✅ Encontrados {$clientCount} clientes en la base de datos");
            $this->line('');

            // Agrupar por company_id y tomar el primer cliente de cada empresa
            $grouped = Client::select('id', 'company_id', 'name', 'email', 'access_token', 'access_token_expires_at')
                ->with('company:id,name')
                ->orderBy('company_id')
                ->orderBy('id')
                ->get()
                ->groupBy('company_id');

            $this->info('=== DEMO TOKENS CLIENTES (válidos 7 días) ===');
            $this->line('');

            $tokenCount = 0;
            $regenerateForce = $this->option('regenerate');

            foreach ($grouped as $companyId => $clients) {
                /** @var Client $client */
                $client = $clients->first();
                
                $needsNewToken = !$client->access_token || 
                               !$client->access_token_expires_at || 
                               !$client->access_token_expires_at->isFuture() ||
                               $regenerateForce;

                if ($needsNewToken) {
                    $token = $client->generateAccessToken();
                    $status = '🆕 NUEVO';
                    $tokenCount++;
                } else {
                    $token = $client->access_token;
                    $status = '♻️ REUTILIZADO';
                }

                $this->line(sprintf(
                    '%s | Empresa: %s | Cliente: %s | Email: %s',
                    $status,
                    $client->company->name ?? "ID {$companyId}",
                    $client->name,
                    $client->email
                ));
                
                $this->line("    🔑 TOKEN: {$token}");
                $this->line("    ⏰ EXPIRA: " . $client->access_token_expires_at->format('Y-m-d H:i:s'));
                $this->line("    🔗 URL: http://localhost:4200/client-portal/access?token={$token}&email=" . urlencode($client->email));
                $this->line('');
            }

            $this->line('============================================');
            
            if ($tokenCount > 0) {
                $this->info("✅ Se generaron {$tokenCount} nuevos tokens");
            } else {
                $this->comment('ℹ️ Todos los tokens existentes siguen vigentes');
                $this->line('💡 Usa --regenerate para forzar nuevos tokens');
            }

            $this->line('');
            $this->comment('💡 Guarda uno de estos tokens para usarlo en el portal del cliente');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error al generar tokens: ' . $e->getMessage());
            return 1;
        }
    }
}