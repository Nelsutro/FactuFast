# Guía de pruebas para enlaces de pago públicos (modo simulación)

Esta guía explica cómo validar extremo a extremo el flujo de pagos públicos con Webpay en modo simulación dentro de **FactuFast**. Está pensada para QA o desarrolladores que necesiten comprobar el comportamiento sin credenciales reales de Transbank.

## 1. Requisitos previos

- **Backend Laravel** funcionando en `api/`.
  - Haber ejecutado `composer install`, configurado `.env`, generado `APP_KEY` y migrado la base de datos.
  - Servidor activo (por ejemplo, `php artisan serve --host=127.0.0.1 --port=8000`).
- **Frontend Angular** funcionando en `site/`.
  - Haber ejecutado `npm install` y levantado `npm start` (puerto 4200 por defecto).
- Al menos una **empresa** y una **factura** registradas. El seeder por defecto crea datos de ejemplo.
- Navegador web moderno.

> **Nota:** El modo simulación se activa automáticamente cuando la empresa no tiene configurados `webpay_commerce_code` y `webpay_api_key`. En ese caso, el sistema marca los pagos como completados de inmediato.

## 2. Flujo resumido

1. Generar (o recuperar) un enlace público firmado para una factura.
2. Abrir ese enlace en el navegador público (`/public-pay/:hash`).
3. Iniciar el pago desde la UI.
4. Observar el resultado simulado y el refresco del estado.

## 3. Generar un enlace público

### 3.1 Desde una petición API autenticada

1. Inicia sesión en el panel administrativo y obtén tu token (el frontend ya se encarga con Sanctum).
2. Identifica el `invoice_id` deseado (por ejemplo, desde `GET /api/invoices`).
3. Realiza una petición `POST` a `/api/invoices/{invoice_id}/payment-link`.

Ejemplo usando `curl` (reemplaza `TOKEN` y `INVOICE_ID`):

```bash
curl -X POST \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  http://127.0.0.1:8000/api/invoices/INVOICE_ID/payment-link
```

La respuesta contiene:

```json
{
  "success": true,
  "data": {
    "hash": "<hash>",
    "public_url": "http://127.0.0.1:8000/api/public/pay/<hash>",
    "expires_at": 1696200000
  }
}
```

- `public_url` es el endpoint API.
- Para el frontend Angular úsalo como `http://localhost:4200/public-pay/<hash>`.

### 3.2 (Opcional) Confirmar expiración

El enlace incluye `expires_at` (timestamp Unix). Si expira, el API responderá `410 Link inválido o expirado`.

## 4. Probar desde la interfaz pública

1. Navega a `http://localhost:4200/public-pay/<hash>`.
2. Verifica que se cargan los datos de la factura (monto, estado, fecha de vencimiento).
3. Comprueba que el botón “Pagar ahora” está habilitado si la factura no está pagada y el enlace no expiró.

### 4.1 Iniciar el pago

- Haz clic en **Pagar ahora**.
- El frontend ejecuta `POST /api/public/pay/{hash}/init`.
- En modo simulación ( sin credenciales), la respuesta indica `is_paid: true` y estado `completed`.

### 4.2 Polling de estado

- Si la respuesta no trae `redirect_url`, el componente inicia `pollPublicPaymentStatus`.
- Se consulta `GET /api/public/pay/{hash}/status?payment_id={id}` cada ~2.5 segundos, hasta 2 minutos.
  - En simulación, la primera respuesta marcará `is_paid = true` → el componente muestra el mensaje verde “Pago confirmado correctamente.”
  - Si el estado pasa a `failed`, el mensaje será de error y se habilita **Reintentar**.

### 4.3 Mensajes esperados

- **Carga inicial:** “Cargando enlace…” mientras llega la API.
- **Simulación exitosa:**
  - Mensaje verde “Pago confirmado correctamente.”
  - Etiqueta de estado cambia a `paid`.
- **Enlace expirado:** aparecen la etiqueta roja y el botón de pago deshabilitado.
- **Error de API:** se muestra un snackbar con el mensaje de error y no se inicia el flujo.

## 5. Casos especiales a validar

| Escenario | Cómo forzarlo | Resultado esperado |
|-----------|---------------|--------------------|
| Enlace expirado | Cambiar el TTL al generar el enlace (p.e. 5 segundos) y esperar | UI muestra “Enlace expirado”, botón deshabilitado, no permite iniciar pago |
| Factura ya pagada | Marcar factura como pagada (`POST /api/invoices/{id}/mark-paid`) antes de abrir el enlace | UI informa que ya está pagada y no ofrece pagar |
| Error de API | Detener el backend o cortar red antes de “Pagar ahora” | Snackbar con mensaje de error y no se cambia el estado |
| Pago fallido | Simulación no cubre fallo, pero cuando haya credenciales reales Webpay podría devolver `failed`; el componente muestra mensaje rojo y botón **Reintentar** |

## 6. Verificar en base de datos

Si quieres confirmar el estado:

- Tabla `payments`: debe registrar el nuevo pago con `status = completed` y `provider = webpay`.
- Tabla `invoices`: el registro asociado cambia a `status = paid`.

Comandos útiles (desde `api/`):

```bash
php artisan tinker
>>> App\Models\Payment::latest()->first();
>>> App\Models\Invoice::find(INVOICE_ID);
```

## 7. Validación posterior con credenciales reales

Cuando se tengan credenciales Webpay:

1. Configura en la empresa (`companies` table) los campos `webpay_commerce_code`, `webpay_api_key`, `webpay_environment` (`integration` o `production`).
2. Repite los pasos de la sección 4.
3. El flujo ahora redirigirá al portal de Webpay (`redirect_url` no es `null`). Al finalizar, Webpay redirige a `return_url` (`/api/payments/webpay/return`).
4. El backend confirmará el pago con `commit` y actualiza la factura.

## 8. Posibles mejoras futuras

- Registrar auditoría de eventos de pago para trazabilidad.
- Personalizar mensajes en la UI según el proveedor (Webpay vs. otros).
- Agregar opción de reenvío del enlace de pago desde el panel.

---

¡Listo! Con esta guía puedes reproducir el flujo público de pagos en modo simulación y validar los distintos estados sin necesidad de credenciales reales.
