<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request): JsonResponse
    {
        // Datos de ejemplo - reemplazar con datos reales
        $stats = [
            'total_invoices' => 156,
            'pending_invoices' => 23,
            'paid_invoices' => 133,
            'total_clients' => 45,
            'total_revenue' => 1250000,
            'pending_revenue' => 180000,
            'this_month_revenue' => 320000,
            'last_month_revenue' => 285000,
            'growth_percentage' => 12.3
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
        $period = $request->get('period', 'monthly');
        
        // Datos de ejemplo - reemplazar con datos reales
        $revenueData = [
            'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            'datasets' => [
                [
                    'label' => 'Ingresos',
                    'data' => [250000, 280000, 320000, 290000, 350000, 320000],
                    'backgroundColor' => '#1976d2',
                    'borderColor' => '#1976d2',
                    'borderWidth' => 2
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $revenueData
        ]);
    }
}
