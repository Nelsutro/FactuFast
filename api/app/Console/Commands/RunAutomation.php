<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\Quote;

class RunAutomation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:run {--dry-run : Muestra lo que haría sin aplicar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta tareas automáticas: marcar facturas vencidas y expirar cotizaciones';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $today = now()->startOfDay();

        // 1) Facturas vencidas: pending -> overdue cuando due_date < hoy
        $invoicesQuery = Invoice::query()
            ->where('status', 'pending')
            ->whereDate('due_date', '<', $today);

        $invoicesCount = (clone $invoicesQuery)->count();
        if ($dryRun) {
            $this->info("[DRY-RUN] Facturas a marcar como vencidas: {$invoicesCount}");
        } else {
            $updatedInvoices = $invoicesQuery->update(['status' => 'overdue']);
            $this->info("Facturas marcadas como vencidas: {$updatedInvoices}");
        }

        // 2) Cotizaciones expiradas: draft|sent -> expired cuando valid_until < hoy
        $quotesQuery = Quote::query()
            ->whereIn('status', ['draft', 'sent'])
            ->whereDate('valid_until', '<', $today);

        $quotesCount = (clone $quotesQuery)->count();
        if ($dryRun) {
            $this->info("[DRY-RUN] Cotizaciones a marcar como expiradas: {$quotesCount}");
        } else {
            $updatedQuotes = $quotesQuery->update(['status' => 'expired']);
            $this->info("Cotizaciones marcadas como expiradas: {$updatedQuotes}");
        }

        $this->line('Automatización finalizada.');
        return self::SUCCESS;
    }
}
