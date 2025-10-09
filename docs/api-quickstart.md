# FactuFast API – Guía rápida

Esta guía resume los pasos para consumir la API de FactuFast usando los recursos documentados en [`api-openapi.yaml`](./api-openapi.yaml). Incluye la obtención de credenciales, convención de entornos y solicitudes de ejemplo.

> **Ambientes de referencia**
>
> - **Certificación:** `https://api.factufast.cert.example.com/api`
> - **Local:** `http://localhost:8000/api`
>
> Asegúrate de reemplazar la base según el entorno entregado por tu equipo.

## 1. Obtener un token de acceso

### 1.1. Desde la interfaz (recomendado)
1. Inicia sesión en la SPA con un usuario administrador o con permisos de gestión.
2. Abre **Configuración → Tokens API**.
3. Crea un token asignando las _abilities_ (scopes) necesarias. Algunos ejemplos:
  - `api:read-dashboard`
  - `api:read-invoices`
  - `api:write-invoices`
  - `api:read-clients`
  - `api:read-payments`
4. Copia el `token` completo (formato `tokenPrefix|secret`). Guárdalo en un gestor seguro; no vuelve a mostrarse.

### 1.2. Via API

```http
POST /settings/api-tokens HTTP/1.1
Host: api.factufast.cert.example.com
Authorization: Bearer <token-de-sesión>
Content-Type: application/json

{
  "name": "Integración ERP",
  "abilities": ["api:read-invoices", "api:read-clients"],
  "rate_limit_per_minute": 300,
  "expires_in_days": 90,
  "description": "Sync ERP"
}
```

La respuesta devuelve `data.token`, que usarás como `Bearer` en el header `Authorization`.

## 2. Autenticación y Headers requeridos

| Contexto                 | Header obligatorio                                        |
|--------------------------|------------------------------------------------------------|
| API protegida (Sanctum)  | `Authorization: Bearer <token>`                             |
| Portal de clientes       | `X-Client-Email: cliente@example.com`<br>`X-Client-Token: <token>` |
| Webhooks hacia FactuFast | Encabezados HMAC configurados por proveedor (Webpay/MercadoPago) |

### Estados de sesión SPA
- Login clásico: `POST /auth/login`
- OAuth: `GET /auth/oauth/{provider}/redirect`
- Refresco: `POST /auth/refresh`

## 3. Llamadas básicas

### 3.1. Listar facturas

```bash
curl -X GET \
  "https://api.factufast.cert.example.com/api/invoices" \
  -H "Authorization: Bearer ${FACTUFAST_TOKEN}" \
  -H "Accept: application/json"
```

### 3.2. Crear factura

```bash
curl -X POST \
  "https://api.factufast.cert.example.com/api/invoices" \
  -H "Authorization: Bearer ${FACTUFAST_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
        "client_id": 42,
        "invoice_number": "FF-2025-00123",
        "amount": 250000,
        "issue_date": "2025-10-01",
        "due_date": "2025-10-15",
        "notes": "Implementación módulo ventas"
      }'
```

### 3.3. Exportar clientes a CSV

```bash
curl -L \
  "https://api.factufast.cert.example.com/api/clients/export" \
  -H "Authorization: Bearer ${FACTUFAST_TOKEN}" \
  -o clientes.csv
```

### 3.4. Importar facturas (scope requerido)

```bash
curl -X POST \
  "https://api.factufast.cert.example.com/api/invoices/import" \
  -H "Authorization: Bearer ${FACTUFAST_TOKEN}" \
  -F "file=@facturas.csv"
```

El token debe incluir el scope `api:import-invoices`. Si no, el middleware `token.policies` devolverá 403.

## 4. Portal de clientes

1. El cliente solicita un acceso temporal mediante `POST /client-portal/request-access`.
2. FactuFast entrega un link con query `token` y `email`.
3. Para consumir recursos:

```bash
curl -X GET \
  "https://api.factufast.cert.example.com/api/client-portal/invoices" \
  -H "X-Client-Email: cliente@example.com" \
  -H "X-Client-Token: ${CLIENT_TOKEN}"
```

Los tokens expiran a los 7 días (configurable en `Client::generateAccessToken`).

## 5. Webhooks de pago

Configura los proveedores para enviar eventos a:

```
POST https://api.factufast.cert.example.com/api/webhooks/payments/{provider}
```

- `provider` admite `webpay` o `mercadopago`.
- Incluye la firma HMAC/secret entregada por FactuFast. El servicio valida encabezados y registra el evento para conciliación.

## 6. Scopes disponibles para tokens API

| Scope                  | Descripción                                                    |
|------------------------|----------------------------------------------------------------|
| `api:read-dashboard`   | Permite consultar `/dashboard/*` y métricas agregadas          |
| `api:manage-settings`  | Gestión de `/settings` y `/settings/api-tokens`                |
| `api:read-invoices`    | Lectura de facturas, enlaces públicos y estadísticas          |
| `api:write-invoices`   | Creación/actualización/eliminación de facturas y pagos ligados |
| `api:import-invoices`  | Importación masiva de facturas vía CSV                         |
| `api:read-clients`     | Acceso a listados y detalles de clientes                       |
| `api:write-clients`    | Alta/baja/edición de clientes e importación                    |
| `api:read-quotes`      | Consultas de cotizaciones y métricas asociadas                 |
| `api:write-quotes`     | Crear/editar/eliminar cotizaciones y convertirlas              |
| `api:import-quotes`    | Importación masiva de cotizaciones vía CSV                     |
| `api:read-payments`    | Lectura de pagos y conciliaciones                              |
| `api:write-payments`   | Registro y actualización manual de pagos                       |
| `api:read-companies`   | Acceder a `/companies` y facturas agregadas por empresa        |

Los scopes se revisan a nivel de middleware (`token.policies`) y en endpoints sensibles (`abilities:`).

## 7. Herramientas recomendadas

- **Insomnia / Postman**: importa `docs/api-openapi.yaml` para generar requests automáticamente.
- **`redoc-cli`**: genera documentación navegable con `npx @redocly/cli build-docs docs/api-openapi.yaml`.
- **CLI Laravel**: utilidades internas, por ejemplo `php artisan passport:purge` (cuando esté habilitado) o scripts personalizados.

## 8. Próximos pasos

- Revisar la definición completa en [`api-openapi.yaml`](./api-openapi.yaml) para detalles de cada respuesta.
- Coordinar la publicación en el portal de partners o intranet.
- Mantener la guía sincronizada con cambios de scopes y nuevos endpoints.
