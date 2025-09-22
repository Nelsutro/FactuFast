<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Comprobante de pago</title>
  <style>body{font-family:Arial,sans-serif;color:#111;}</style>
</head>
<body>
  <p>Hola {{ $invoice->client->name ?? 'cliente' }},</p>
  <p>Hemos recibido su pago por ${{ number_format($payment->amount, 2, ',', '.') }} correspondiente a la factura #{{ $invoice->invoice_number }}.</p>
  <p>Fecha de pago: {{ optional($payment->payment_date)->format('d/m/Y') }}<br>
     Método: {{ $payment->payment_method }}</p>
  <p>¡Gracias por su pago!</p>
  <p>— {{ $invoice->company->name ?? 'FactuFast' }}</p>
</body>
</html>
