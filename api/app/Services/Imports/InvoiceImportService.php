<?php

namespace App\Services\Imports;

use App\Models\Client;
use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class InvoiceImportService
{
    public function processRow(ImportBatch $batch, array $data, int $rowNumber): void
    {
        $identifier = $data['invoice_number'] ?? null;

        try {
            $payload = $this->normalizeRow($data);
        } catch (\Throwable $e) {
            $this->registerError($batch, $rowNumber, $identifier, $e->getMessage(), $data);
            return;
        }

        try {
            retry(3, function () use ($batch, $payload, $rowNumber, $data, $identifier) {
                DB::transaction(function () use ($batch, $payload, $rowNumber, $data, $identifier) {
                    $client = Client::where('company_id', $batch->company_id)
                        ->where('email', $payload['client_email'])
                        ->first();

                    if (!$client) {
                        throw new \RuntimeException("Cliente no encontrado ({$payload['client_email']})");
                    }

                    $exists = Invoice::where('company_id', $batch->company_id)
                        ->where(function ($query) use ($payload) {
                            $query->where('invoice_number', $payload['invoice_number']);
                        })
                        ->exists();

                    if ($exists) {
                        throw new \RuntimeException("Factura duplicada ({$payload['invoice_number']})");
                    }

                    $invoice = Invoice::create([
                        'company_id' => $batch->company_id,
                        'client_id' => $client->id,
                        'invoice_number' => $payload['invoice_number'],
                        'amount' => $payload['amount'],
                        'status' => $payload['status'],
                        'issue_date' => $payload['issue_date']->format('Y-m-d'),
                        'due_date' => $payload['due_date']->format('Y-m-d'),
                        'notes' => $payload['notes'],
                    ]);

                    ImportBatchRow::create([
                        'import_batch_id' => $batch->id,
                        'row_number' => $rowNumber,
                        'status' => 'success',
                        'identifier' => $invoice->invoice_number,
                        'message' => null,
                        'payload' => $data,
                    ]);

                    $batch->incrementEach(['processed_rows' => 1, 'success_count' => 1]);
                }, 5);
            }, 150, function ($e) {
                return $e instanceof QueryException || $e instanceof \PDOException;
            });
        } catch (\Throwable $e) {
            $this->registerError($batch, $rowNumber, $identifier, $e->getMessage(), $data);
        }
    }

    protected function normalizeRow(array $row): array
    {
        $invoiceNumber = trim((string)($row['invoice_number'] ?? ''));
        if ($invoiceNumber === '') {
            throw new \InvalidArgumentException('invoice_number vacío');
        }

        $clientEmail = trim((string)($row['client_email'] ?? ''));
        if ($clientEmail === '') {
            throw new \InvalidArgumentException('client_email vacío');
        }
        if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("email inválido ({$clientEmail})");
        }

        $amountRaw = $row['amount'] ?? '';
        if ($amountRaw === '' || !is_numeric($amountRaw)) {
            throw new \InvalidArgumentException('amount inválido');
        }

        $status = strtolower(trim((string)($row['status'] ?? 'pending')));
        $allowedStatuses = ['draft','pending','paid','overdue','cancelled'];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new \InvalidArgumentException("status inválido ({$status})");
        }

        $issueDate = $this->parseDate($row['issue_date'] ?? null, 'issue_date');
        $dueDate = $this->parseDate($row['due_date'] ?? null, 'due_date');

        if ($dueDate->lt($issueDate)) {
            throw new \InvalidArgumentException('due_date anterior a issue_date');
        }

        $notes = trim((string)($row['notes'] ?? '')) ?: null;

        return [
            'invoice_number' => $invoiceNumber,
            'client_email' => $clientEmail,
            'amount' => (float)$amountRaw,
            'status' => $status,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'notes' => $notes,
        ];
    }

    protected function parseDate($value, string $field): Carbon
    {
        if (!$value) {
            throw new \InvalidArgumentException("{$field} vacío");
        }

        try {
            $date = Carbon::parse($value);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException("{$field} inválido");
        }

        return $date->startOfDay();
    }

    protected function registerError(ImportBatch $batch, int $rowNumber, ?string $identifier, string $message, array $data): void
    {
        ImportBatchRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => $rowNumber,
            'status' => 'error',
            'identifier' => $identifier,
            'message' => $message,
            'payload' => $data,
        ]);

        $batch->incrementEach(['processed_rows' => 1, 'error_count' => 1]);
    }
}
