<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso a tu Portal de Facturas</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1976d2;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #1976d2;
            margin: 0;
            font-size: 28px;
        }
        .header .company {
            color: #666;
            font-size: 16px;
            margin-top: 5px;
        }
        .content {
            margin-bottom: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }
        .message {
            margin-bottom: 20px;
            font-size: 16px;
            color: #555;
        }
        .access-section {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        .access-button {
            display: inline-block;
            background-color: #1976d2;
            color: white !important;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 15px 0;
            transition: background-color 0.3s;
        }
        .access-button:hover {
            background-color: #1565c0;
        }
        .token-info {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .token-info strong {
            color: #856404;
        }
        .security-note {
            background-color: #e8f5e8;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
        }
        .footer {
            border-top: 1px solid #e9ecef;
            padding-top: 20px;
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .features {
            margin: 25px 0;
        }
        .features h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .features ul {
            list-style: none;
            padding: 0;
        }
        .features li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .features li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
        .expires {
            color: #dc3545;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>FactuFast</h1>
            <div class="company">Portal de Facturas - {{ $companyName }}</div>
        </div>

        <div class="content">
            <div class="greeting">
                ¡Hola {{ $client->name }}! 👋
            </div>

            <div class="message">
                Te hemos preparado acceso a tu <strong>Portal Personal de Facturas</strong> donde podrás ver y pagar todas tus facturas de forma segura y conveniente.
            </div>

            @if($customMessage)
            <div class="message">
                <em>{{ $customMessage }}</em>
            </div>
            @endif

            <div class="access-section">
                <h3>🔐 Tu Acceso Personal</h3>
                <p>Haz clic en el botón de abajo para acceder a tu portal:</p>
                
                <a href="{{ $accessUrl }}" class="access-button">
                    🚀 Acceder a mi Portal
                </a>

                <div class="token-info">
                    <strong>⏰ Este enlace expira el:</strong><br>
                    <span class="expires">{{ $expiresAt ? $expiresAt->format('d/m/Y a las H:i') . ' hrs' : 'Sin vencimiento' }}</span>
                </div>
            </div>

            <div class="features">
                <h3>📋 ¿Qué puedes hacer en tu portal?</h3>
                <ul>
                    <li>Ver todas tus facturas pendientes y pagadas</li>
                    <li>Descargar facturas en PDF</li>
                    <li>Realizar pagos online seguros</li>
                    <li>Recibir notificaciones de vencimiento</li>
                    <li>Acceso 24/7 desde cualquier dispositivo</li>
                </ul>
            </div>

            <div class="security-note">
                <strong>🔒 Seguridad garantizada:</strong><br>
                Este enlace es único y personal. No lo compartas con nadie. Tus pagos son procesados a través de pasarelas certificadas y tus datos están completamente protegidos.
            </div>
        </div>

        <div class="footer">
            <p>
                <strong>{{ config('app.name', 'FactuFast') }}</strong><br>
                Sistema de Facturación y Pagos
            </p>
            <p>
                Si tienes problemas para acceder, contacta a:<br>
                📧 <a href="mailto:{{ config('services.flow.notification_email', 'pagos@factufast.cl') }}">{{ config('services.flow.notification_email', 'pagos@factufast.cl') }}</a>
            </p>
            <p style="font-size: 12px; color: #999; margin-top: 15px;">
                Este email fue enviado automáticamente. Por favor no responder a este mensaje.
            </p>
        </div>
    </div>
</body>
</html>