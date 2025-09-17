<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .company h2 { margin: 0 0 4px 0; font-size: 18px; }
        .muted { color: #666; }
        .box { border: 1px solid #ddd; padding: 8px; border-radius: 4px; }
        .title { font-size: 20px; margin: 0 0 8px 0; }
        .grid { display: flex; gap: 12px; }
        .col { flex: 1; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 8px; border: 1px solid #ddd; }
        th { background: #f4f4f5; text-align: left; }
        .right { text-align: right; }
        .totals { margin-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">
            <h2>{{ $invoice->company->name ?? 'Empresa' }}</h2>
            <div class="muted">RUT/NIF: {{ $invoice->company->tax_id ?? '-' }}</div>
            <div class="muted">Email: {{ $invoice->company->email ?? '-' }}</div>
            <div class="muted">Tel: {{ $invoice->company->phone ?? '-' }}</div>
            <div class="muted">{{ $invoice->company->address ?? '-' }}</div>
        </div>
        <div class="box">
            <div class="title">Factura #{{ $invoice->invoice_number }}</div>
            <div>Fecha emisión: {{ optional($invoice->issue_date)->format('d/m/Y') }}</div>
            <div>Vencimiento: {{ optional($invoice->due_date)->format('d/m/Y') }}</div>
            <div>Estado: {{ ucfirst($invoice->status) }}</div>
        </div>
    </div>

    <div class="grid">
        <div class="col box">
            <strong>Facturar a</strong>
            <div>{{ $invoice->client->name ?? 'Cliente' }}</div>
            <div class="muted">{{ $invoice->client->email ?? '-' }}</div>
            <div class="muted">{{ $invoice->client->phone ?? '-' }}</div>
            <div class="muted">{{ $invoice->client->address ?? '-' }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="right">Cantidad</th>
                <th class="right">P. Unitario</th>
                <th class="right">Importe</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($item->amount, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="right">Sin ítems</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals">
        <div class="right"><strong>Total: </strong>{{ number_format($invoice->amount, 2, ',', '.') }}</div>
    </div>

    @if($invoice->notes)
    <div class="box" style="margin-top:12px;">
        <strong>Notas</strong>
        <div>{{ $invoice->notes }}</div>
    </div>
    @endif
</body>
</html>
