<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Factura #{{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; color:#111;">
  <p>Hola {{ $invoice->client->name ?? 'cliente' }},</p>
  <p>{!! nl2br(e($bodyMessage)) !!}</p>
  <p>
    Detalles:
    <br>Factura: #{{ $invoice->invoice_number }}
    <br>Monto: ${{ number_format($invoice->amount, 2, ',', '.') }}
    <br>Vencimiento: {{ optional($invoice->due_date)->format('d/m/Y') }}
  </p>
  <p>Saludos,<br>{{ $invoice->company->name ?? 'Nuestro equipo' }}</p>
</body>
</html>
