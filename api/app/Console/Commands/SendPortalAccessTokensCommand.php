<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Mail\ClientPortalAccessMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendPortalAccessTokensCommand extends Command
{
    protected $signature = 'portal:send-tokens 
                           {--email= : Enviar solo a este email específico}
                           {--company= : Enviar solo a clientes de esta empresa (ID)}
                           {--regenerate : Forzar regeneración de tokens existentes}
                           {--message= : Mensaje personalizado para incluir en el email}
                           {--dry-run : Solo mostrar qué se enviaría, sin enviar realmente}';
    
    protected $description = 'Enviar tokens de acceso al portal de clientes por email';

    public function handle()
    {
        $this->info('📧 Enviando tokens de acceso al portal de clientes...');
        $this->line('');

        // Validar configuración de correo
        if (!$this->validateMailConfig()) {
            return 1;
        }

        try {
            $clients = $this->getTargetClients();
            
            if ($clients->isEmpty()) {
                $this->error('❌ No se encontraron clientes que cumplan los criterios especificados.');
                return 1;
            }

            $this->info("✅ Encontrados {$clients->count()} clientes para envío");
            $this->line('');

            $isDryRun = $this->option('dry-run');
            $customMessage = $this->option('message');
            $regenerateForce = $this->option('regenerate');

            if ($isDryRun) {
                $this->warn('🧪 MODO DRY-RUN: No se enviarán emails reales');
                $this->line('');
            }

            $sentCount = 0;
            $errorCount = 0;

            foreach ($clients as $client) {
                try {
                    // Verificar si necesita nuevo token
                    $needsNewToken = !$client->access_token || 
                                   !$client->access_token_expires_at || 
                                   !$client->access_token_expires_at->isFuture() ||
                                   $regenerateForce;

                    if ($needsNewToken) {
                        $token = $client->generateAccessToken();
                        $tokenStatus = '🆕 NUEVO TOKEN';
                    } else {
                        $token = $client->access_token;
                        $tokenStatus = '♻️ TOKEN EXISTENTE';
                    }

                    $this->line(sprintf(
                        '%s | %s | %s | %s',
                        $tokenStatus,
                        $client->company->name ?? "Sin empresa",
                        $client->name,
                        $client->email
                    ));

                    if (!$isDryRun) {
                        // Enviar email
                        Mail::to($client->email, $client->name)
                            ->send(new ClientPortalAccessMail($client, $token, $customMessage));
                        
                        $this->line("    ✅ Email enviado exitosamente");
                        $sentCount++;
                    } else {
                        $this->line("    🧪 Email preparado (no enviado por dry-run)");
                    }
                    
                    $this->line("    ⏰ Expira: " . $client->access_token_expires_at->format('Y-m-d H:i:s'));
                    $this->line('');

                } catch (\Exception $e) {
                    $this->error("    ❌ Error enviando a {$client->email}: " . $e->getMessage());
                    $errorCount++;
                    $this->line('');
                }
            }

            $this->line('============================================');
            
            if ($isDryRun) {
                $this->info("🧪 DRY-RUN completado: {$clients->count()} emails preparados");
            } else {
                $this->info("✅ Proceso completado:");
                $this->line("   📤 Emails enviados: {$sentCount}");
                if ($errorCount > 0) {
                    $this->line("   ❌ Errores: {$errorCount}");
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error general: ' . $e->getMessage());
            return 1;
        }
    }

    private function validateMailConfig(): bool
    {
        $mailer = config('mail.default');
        $fromAddress = config('mail.from.address');

        if ($mailer === 'log') {
            $this->error('❌ Configuración de correo en modo LOG. Actualiza MAIL_MAILER en .env');
            $this->line('   Ejemplo: MAIL_MAILER=smtp');
            return false;
        }

        if (empty($fromAddress) || $fromAddress === 'hello@example.com') {
            $this->error('❌ Email remitente no configurado correctamente.');
            $this->line('   Actualiza MAIL_FROM_ADDRESS en .env');
            return false;
        }

        $this->info("📧 Configuración de correo: {$mailer} desde {$fromAddress}");
        return true;
    }

    private function getTargetClients()
    {
        $query = Client::with('company');

        // Filtrar por email específico
        if ($email = $this->option('email')) {
            $query->where('email', $email);
        }

        // Filtrar por empresa
        if ($companyId = $this->option('company')) {
            $query->where('company_id', $companyId);
        }

        // Por defecto, obtener TODOS los clientes (no solo uno por empresa)
        return $query->orderBy('company_id')->orderBy('name')->get();
    }
}