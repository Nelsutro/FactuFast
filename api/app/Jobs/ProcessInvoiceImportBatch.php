<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\Imports\InvoiceImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessInvoiceImportBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $batchId)
    {
    }

    public function handle(InvoiceImportService $service): void
    {
        /** @var ImportBatch|null $batch */
        $batch = ImportBatch::find($this->batchId);
        if (!$batch) {
            return;
        }

        $batch->markProcessing();

        try {
            $disk = Storage::disk('local');
            if (!$disk->exists($batch->stored_path)) {
                $batch->markFailed('Archivo de importación no encontrado');
                return;
            }

            $handle = $disk->readStream($batch->stored_path);
            if (!$handle) {
                $batch->markFailed('No se pudo abrir el archivo de importación');
                return;
            }

            $header = null;
            $rowNumber = 0;

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                $rowNumber++;

                if ($rowNumber === 1) {
                    $header = array_map(static fn($value) => strtolower(trim($value)), $row);
                    continue;
                }

                $data = $this->mapRow($header, $row);
                $service->processRow($batch, $data, $rowNumber);
            }

            fclose($handle);

            $batch->update(['total_rows' => max(0, $rowNumber - 1)]);
            $batch->markCompleted();
        } catch (\Throwable $e) {
            $batch->markFailed($e->getMessage());
            throw $e;
        }
    }

    private function mapRow(?array $header, array $row): array
    {
        if (!$header) {
            $header = ['invoice_number','client_email','amount','status','issue_date','due_date','notes'];
        }

        $row = array_pad($row, count($header), null);

        return array_combine($header, $row) ?: [];
    }
}
