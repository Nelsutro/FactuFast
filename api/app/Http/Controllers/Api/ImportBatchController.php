<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportBatchController extends Controller
{
    public function show(ImportBatch $batch): JsonResponse
    {
        $this->authorizeBatch($batch);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $batch->id,
                'type' => $batch->type,
                'status' => $batch->status,
                'source_filename' => $batch->source_filename,
                'total_rows' => $batch->total_rows,
                'processed_rows' => $batch->processed_rows,
                'success_count' => $batch->success_count,
                'error_count' => $batch->error_count,
                'started_at' => optional($batch->started_at)->toDateTimeString(),
                'finished_at' => optional($batch->finished_at)->toDateTimeString(),
                'meta' => $batch->meta,
            ]
        ]);
    }

    public function errors(ImportBatch $batch): JsonResponse
    {
        $this->authorizeBatch($batch);

        $rows = $batch->rows()
            ->where('status', 'error')
            ->orderBy('row_number')
            ->limit(200)
            ->get(['row_number','identifier','message','payload']);

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function downloadErrors(ImportBatch $batch): StreamedResponse
    {
        $this->authorizeBatch($batch);

        $rows = $batch->rows()->where('status', 'error')->orderBy('row_number')->cursor();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="import_errors_' . $batch->id . '.csv"'
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['row_number', 'identifier', 'message', 'payload']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->row_number,
                    $row->identifier,
                    $row->message,
                    json_encode($row->payload),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function authorizeBatch(ImportBatch $batch): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'No autenticado');
        }

        if ($user->role !== 'admin' && $batch->company_id !== $user->company_id) {
            abort(403, 'No autorizado');
        }
    }
}
