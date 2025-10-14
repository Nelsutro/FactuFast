<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportBatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'No autenticado');
        }

        $query = ImportBatch::query()
            ->with(['user:id,name,email', 'company:id,name']);

        if ($user->role !== 'admin') {
            $query->where('company_id', $user->company_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $statusValue = (string) $request->string('status');
            $statuses = collect(explode(',', $statusValue))
                ->map(static fn ($status) => trim($status))
                ->filter()
                ->unique();
            if ($statuses->isNotEmpty()) {
                $query->whereIn('status', $statuses->all());
            }
        }

        if ($request->boolean('alerts_only')) {
            $query->where(static function ($builder) {
                $builder
                    ->where('status', 'failed')
                    ->orWhere('error_count', '>', 0);
            });
        }

        if ($request->filled('from')) {
            $from = Carbon::parse((string) $request->string('from'))->startOfDay();
            $query->where('created_at', '>=', $from);
        }

        if ($request->filled('to')) {
            $to = Carbon::parse((string) $request->string('to'))->endOfDay();
            $query->where('created_at', '<=', $to);
        }

        $perPage = max(1, min(50, (int) $request->integer('per_page', 10)));
        $batches = $query->orderByDesc('created_at')->paginate($perPage);

        $data = $batches->getCollection()->map(function (ImportBatch $batch) {
            return $this->serializeBatch($batch);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $batches->currentPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
                'last_page' => $batches->lastPage(),
            ],
        ]);
    }

    public function show(ImportBatch $batch): JsonResponse
    {
        $this->authorizeBatch($batch);

        return response()->json([
            'success' => true,
            'data' => $this->serializeBatch($batch),
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

    protected function serializeBatch(ImportBatch $batch): array
    {
        $batch->loadMissing(['user:id,name,email', 'company:id,name']);
        $meta = $batch->meta ?? [];

        return [
            'id' => $batch->id,
            'type' => $batch->type,
            'status' => $batch->status,
            'alert_level' => $batch->alert_level,
            'source_filename' => $batch->source_filename,
            'total_rows' => $batch->total_rows,
            'processed_rows' => $batch->processed_rows,
            'success_count' => $batch->success_count,
            'error_count' => $batch->error_count,
            'started_at' => optional($batch->started_at)->toDateTimeString(),
            'finished_at' => optional($batch->finished_at)->toDateTimeString(),
            'duration_seconds' => $batch->duration_seconds,
            'summary_message' => $batch->summary_message,
            'meta' => $meta,
            'notified_at' => $meta['notified_at'] ?? null,
            'last_error_message' => $meta['error'] ?? null,
            'user' => $batch->user?->only(['id', 'name', 'email']),
            'company' => $batch->company?->only(['id', 'name']),
            'has_errors' => $batch->has_errors,
            'download_errors_url' => $batch->has_errors
                ? route('import-batches.errors.export', ['batch' => $batch->id])
                : null,
        ];
    }
}
