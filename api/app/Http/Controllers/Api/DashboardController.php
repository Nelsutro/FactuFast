<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Consultas base
        $invoiceQuery = Invoice::query();
        $quoteQuery = Quote::query();
        $paymentQuery = Payment::query();

        // Contextualización por rol/empresa (multi-tenant)
        if (!$user->isAdmin()) {
            $invoiceQuery->where('company_id', $user->company_id);
            $quoteQuery->where('company_id', $user->company_id);
            $paymentQuery->whereHas('invoice', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        // Métricas principales
        $pendingInvoices = (clone $invoiceQuery)->where('status', 'pending')->count();
        $paidInvoices = (clone $invoiceQuery)->where('status', 'paid')->count();
        $overdueInvoices = (clone $invoiceQuery)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->count();
        $totalRevenue = (clone $invoiceQuery)->where('status', 'paid')->sum('amount');

        // Cotizaciones
        $activeQuotes = (clone $quoteQuery)->whereIn('status', ['sent', 'accepted'])->count();
        $pendingQuotes = (clone $quoteQuery)->where('status', 'sent')->count();

        // Actividad reciente
        $recentInvoices = (clone $invoiceQuery)
            ->with(['client'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        $recentPayments = (clone $paymentQuery)
            ->where('payment_date', '>=', now()->subDays(30))
            ->count();

        $importQuery = ImportBatch::query()->where('type', 'invoices');
        if (!$user->isAdmin()) {
            $importQuery->where('company_id', $user->company_id);
        }

        $recentImportBatches = (clone $importQuery)
            ->where('created_at', '>=', now()->subDays(7))
            ->get([
                'id',
                'processed_rows',
                'success_count',
                'error_count',
                'started_at',
                'finished_at',
                'created_at',
                'status',
            ]);

        $rowsProcessed = (int) $recentImportBatches->sum('processed_rows');
        $successRows = (int) $recentImportBatches->sum('success_count');
        $errorRows = (int) $recentImportBatches->sum('error_count');

        $avgDuration = $recentImportBatches
            ->filter(static fn (ImportBatch $batch) => $batch->started_at && $batch->finished_at)
            ->avg(static fn (ImportBatch $batch) => $batch->started_at->diffInSeconds($batch->finished_at));

        $pendingBatches = (clone $importQuery)
            ->whereIn('status', ['pending', 'processing'])
            ->count();
        $failedBatches = (clone $importQuery)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $lastImport = (clone $importQuery)->latest('created_at')->first();

        $stats = [
            'pending_invoices' => $pendingInvoices,
            'paid_invoices' => $paidInvoices,
            'overdue_invoices' => $overdueInvoices,
            'total_revenue' => (float) $totalRevenue,
            'active_quotes' => $activeQuotes,
            'pending_quotes' => $pendingQuotes,
            'recent_invoices' => $recentInvoices,
            'recent_payments' => $recentPayments,
            'import_metrics' => [
                'last_import_at' => $lastImport?->created_at?->toISOString(),
                'recent_batches' => $recentImportBatches->count(),
                'rows_processed' => $rowsProcessed,
                'success_rate' => ($successRows + $errorRows) > 0
                    ? round(($successRows / max(1, $successRows + $errorRows)) * 100, 1)
                    : null,
                'error_rows' => $errorRows,
                'avg_duration_seconds' => $avgDuration ? (int) round($avgDuration) : null,
                'pending_batches' => $pendingBatches,
                'failed_batches' => $failedBatches,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get revenue chart data
     */
    public function revenue(Request $request): JsonResponse
    {
        $period = $request->get('period', 'monthly'); // 'monthly' (6 meses) o 'yearly' (12 meses)
        $user = $request->user();

        // Base query para ingresos (facturas pagadas)
        $base = Invoice::query()->where('status', 'paid');
        if (!$user->isAdmin()) {
            $base->where('company_id', $user->company_id);
        }

        $labels = [];
        $data = [];

        if ($period === 'yearly') {
            $year = now()->year;
            for ($m = 1; $m <= 12; $m++) {
                $start = Carbon::create($year, $m, 1)->startOfMonth();
                $end = Carbon::create($year, $m, 1)->endOfMonth();
                $sum = (clone $base)
                    ->whereBetween('issue_date', [$start, $end])
                    ->sum('amount');
                $labels[] = $start->format('M');
                $data[] = (float) $sum;
            }
        } else {
            // monthly: últimos 6 meses (incluye el actual)
            for ($i = 5; $i >= 0; $i--) {
                $start = now()->copy()->subMonths($i)->startOfMonth();
                $end = now()->copy()->subMonths($i)->endOfMonth();
                $sum = (clone $base)
                    ->whereBetween('issue_date', [$start, $end])
                    ->sum('amount');
                $labels[] = $start->format('M');
                $data[] = (float) $sum;
            }
        }

        $revenueData = [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Ingresos',
                'data' => $data,
                'backgroundColor' => '#1976d2',
                'borderColor' => '#1976d2',
                'borderWidth' => 2
            ]]
        ];

        return response()->json([
            'success' => true,
            'data' => $revenueData
        ]);
    }
}
