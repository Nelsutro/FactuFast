<?php

namespace App\Mail;

use App\Models\ImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ImportBatchSummary extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ImportBatch $batch)
    {
        $this->batch->loadMissing(['user', 'company']);
    }

    public function build(): self
    {
        $subject = match ($this->batch->status) {
            'failed' => 'Importación de facturas fallida',
            'completed' => $this->batch->error_count > 0
                ? 'Importación de facturas completada con observaciones'
                : 'Importación de facturas completada',
            default => 'Actualización de importación de facturas',
        };

        return $this
            ->subject($subject)
            ->markdown('emails.import.batch-summary', [
                'batch' => $this->batch,
                'user' => $this->batch->user,
                'company' => $this->batch->company,
            ]);
    }
}
