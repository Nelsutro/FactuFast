<?php

namespace App\Jobs;

use App\Mail\ImportBatchSummary;
use App\Models\ImportBatch;
use App\Services\Imports\InvoiceImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ProcessInvoiceImportBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var int[]|int */
    public $backoff = [30, 120, 300];

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

        $batch->syncCounters();

        $attempt = (int) ($batch->meta['attempts'] ?? 0) + 1;
        $batch->markProcessing($attempt);

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

            $processedRows = array_flip($batch->rows()->pluck('row_number')->all());
            $header = null;
            $rowNumber = 0;

            try {
                while (($row = fgetcsv($handle, 0, ',')) !== false) {
                    $rowNumber++;

                    if ($rowNumber === 1) {
                        $header = array_map(static fn($value) => strtolower(trim($value)), $row);
                        continue;
                    }

                    if (isset($processedRows[$rowNumber])) {
                        continue;
                    }

                    $data = $this->mapRow($header, $row);
                    $service->processRow($batch, $data, $rowNumber);
                }
            } finally {
                fclose($handle);
            }

            $batch->update(['total_rows' => max(0, $rowNumber - 1)]);
            $batch->markCompleted();
            $this->notifyStakeholders($batch);
        } catch (\Throwable $e) {
            $batch->markFailed($e->getMessage());
            $this->notifyStakeholders($batch);
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

    private function notifyStakeholders(ImportBatch $batch): void
    {
        $batch->refresh(['user', 'company']);

        $meta = $batch->meta ?? [];
        $notifiedStatuses = $meta['notified_statuses'] ?? [];
        if (in_array($batch->status, $notifiedStatuses, true)) {
            return;
        }

        $recipients = collect([
            optional($batch->user)->email,
            optional($batch->company)->email,
        ])->filter()->unique()->values();

        if ($recipients->isNotEmpty()) {
            foreach ($recipients as $email) {
                Mail::to($email)->queue(new ImportBatchSummary($batch));
            }
        }

        $meta['notified_at'] = now()->toISOString();
        $notifiedChannels = $meta['notified_channels'] ?? [];
        $notifiedChannels[] = 'email';
        $meta['notified_channels'] = array_values(array_unique($notifiedChannels));
        $notifiedStatuses[] = $batch->status;
        $meta['notified_statuses'] = array_values(array_unique($notifiedStatuses));

        $batch->forceFill(['meta' => $meta])->save();
    }
}
