<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Pago recibido</title>
  <style>body{font-family:Arial,sans-serif;color:#111;}</style>
</head>
<body>
  <p>Hola equipo {{ $invoice->company->name ?? '' }},</p>
  <p>Se registró un pago de ${{ number_format($payment->amount, 2, ',', '.') }} para la factura #{{ $invoice->invoice_number }} del cliente {{ $invoice->client->name ?? 'N/D' }}.</p>
  <p>Fecha: {{ optional($payment->payment_date)->format('d/m/Y') }} | Método: {{ $payment->payment_method }}</p>
  <p>— FactuFast</p>
</body>
</html>
