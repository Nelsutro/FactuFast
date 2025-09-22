<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Nueva factura</title>
  <style>body{font-family:Arial,sans-serif;color:#111;}</style>
  </head>
<body>
  <p>Hola {{ $invoice->client->name ?? 'cliente' }},</p>
  <p>Se ha emitido una nueva factura #{{ $invoice->invoice_number }} por un monto de ${{ number_format($invoice->amount, 2, ',', '.') }}.</p>
  <p>Fecha de emisión: {{ optional($invoice->issue_date)->format('d/m/Y') }}<br>
     Fecha de vencimiento: {{ optional($invoice->due_date)->format('d/m/Y') }}</p>
  <p>Gracias por su preferencia.</p>
  <p>— {{ $invoice->company->name ?? 'FactuFast' }}</p>
</body>
</html>
