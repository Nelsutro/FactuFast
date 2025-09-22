<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Factura creada</title>
  <style>body{font-family:Arial,sans-serif;color:#111;}</style>
</head>
<body>
  <p>Hola equipo {{ $invoice->company->name ?? '' }},</p>
  <p>Se creó la factura #{{ $invoice->invoice_number }} para el cliente {{ $invoice->client->name ?? 'N/D' }} por ${{ number_format($invoice->amount, 2, ',', '.') }}.</p>
  <p>Emisión: {{ optional($invoice->issue_date)->format('d/m/Y') }} | Vencimiento: {{ optional($invoice->due_date)->format('d/m/Y') }}</p>
  <p>— FactuFast</p>
</body>
</html>
