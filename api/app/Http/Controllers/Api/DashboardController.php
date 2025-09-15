<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        $stats = [
            'pending_invoices' => $pendingInvoices,
            'paid_invoices' => $paidInvoices,
            'overdue_invoices' => $overdueInvoices,
            'total_revenue' => (float) $totalRevenue,
            'active_quotes' => $activeQuotes,
            'pending_quotes' => $pendingQuotes,
            'recent_invoices' => $recentInvoices,
            'recent_payments' => $recentPayments,
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
