# API Flow.cl - Documentación

## Resumen de Implementación

Se ha implementado completamente la integración con Flow.cl en FactuFast con los siguientes componentes:

### 📊 Base de Datos
- **Tabla `payments`**: Extendida con campos específicos de Flow
- **Tabla `flow_customers`**: Gestión de clientes Flow para pagos recurrentes  
- **Tabla `refunds`**: Gestión de reembolsos Flow

### 🔧 Servicios
- **FlowService**: Servicio completo para integración con API Flow.cl
  - Autenticación HMAC SHA256
  - Creación y consulta de pagos
  - Gestión de clientes Flow
  - Procesamiento de reembolsos
  - Validación de webhooks

### 🎯 Controladores
- **FlowPaymentController**: Endpoints para pagos Flow
- **RefundController**: Endpoints para reembolsos

### 🛣️ Modelos
- **Payment**: Extendido para Flow
- **FlowCustomer**: Modelo para clientes Flow
- **Refund**: Modelo para reembolsos

## 📡 API Endpoints

### Pagos Directos

#### Crear Pago Directo
```http
POST /api/flow/payments/direct
Authorization: Bearer {token}
Content-Type: application/json

{
    "amount": 15000,
    "subject": "Factura #123 - Desarrollo Web",
    "email": "cliente@ejemplo.com",
    "urlReturn": "https://miapp.com/payment/success",
    "urlConfirmation": "https://miapp.com/api/webhooks/flow/payment-confirmation",
    "optional": "metadata adicional",
    "timeout": 1800
}
```

#### Consultar Estado de Pago
```http
GET /api/flow/payments/{payment_id}/status
Authorization: Bearer {token}
```

#### Listar Pagos
```http
GET /api/flow/payments?status=completed&date_from=2025-01-01
Authorization: Bearer {token}
```

### Clientes Flow (Pagos Recurrentes)

#### Crear Cliente Flow
```http
POST /api/flow/customers
Authorization: Bearer {token}
Content-Type: application/json

{
    "external_id": "cliente_123",
    "name": "Juan Pérez",
    "email": "juan@ejemplo.com",
    "url_return": "https://miapp.com/card/registered"
}
```

#### Crear Pago con Cliente Registrado
```http
POST /api/flow/customers/{customer_id}/charge
Authorization: Bearer {token}
Content-Type: application/json

{
    "amount": 25000,
    "subject": "Suscripción mensual",
    "url_confirmation": "https://miapp.com/api/webhooks/flow/payment-confirmation",
    "optional": "suscripcion_premium"
}
```

### Reembolsos

#### Crear Reembolso
```http
POST /api/flow/refunds
Authorization: Bearer {token}
Content-Type: application/json

{
    "payment_id": 123,
    "amount": 5000,
    "reason": "Producto defectuoso",
    "url_callback": "https://miapp.com/api/webhooks/flow/refund-confirmation"
}
```

#### Consultar Estado de Reembolso
```http
GET /api/flow/refunds/{refund_id}/status
Authorization: Bearer {token}
```

#### Listar Reembolsos
```http
GET /api/flow/refunds?status=completed&payment_id=123
Authorization: Bearer {token}
```

### Webhooks (Públicos)

#### Confirmación de Pago
```http
POST /api/webhooks/flow/payment-confirmation
Content-Type: application/x-www-form-urlencoded

token=abc123&flowOrder=12345&s=signature
```

#### Confirmación de Reembolso  
```http
POST /api/webhooks/flow/refund-confirmation
Content-Type: application/x-www-form-urlencoded

flowRefundOrder=ref123&s=signature
```

## 🔒 Configuración

### Variables de Entorno
```env
# Flow.cl API
FLOW_API_KEY=tu_api_key
FLOW_SECRET_KEY=tu_secret_key  
FLOW_ENVIRONMENT=sandbox
FLOW_API_URL=https://sandbox.flow.cl/api
```

### Configuración Laravel
```php
// config/services.php
'flow' => [
    'api_key' => env('FLOW_API_KEY'),
    'secret_key' => env('FLOW_SECRET_KEY'),
    'environment' => env('FLOW_ENVIRONMENT', 'sandbox'),
    'api_url' => env('FLOW_API_URL', 'https://sandbox.flow.cl/api'),
],
```

## 📄 Respuestas de API

### Respuesta Exitosa de Pago
```json
{
    "status": "success",
    "message": "Pago creado exitosamente",
    "data": {
        "payment_id": 123,
        "flow_order": "F12345",
        "token": "abc123xyz",
        "url": "https://sandbox.flow.cl/app/web/pay.php?token=abc123xyz"
    }
}
```

### Respuesta de Error
```json
{
    "status": "error", 
    "message": "Error al crear el pago: Insufficient funds",
    "errors": {
        "amount": ["El monto es requerido"]
    }
}
```

## 🔄 Estados de Pago

- **pending**: Pago pendiente
- **completed**: Pago completado exitosamente
- **failed**: Pago falló
- **cancelled**: Pago cancelado

## 🔄 Estados de Reembolso

- **pending**: Reembolso pendiente
- **completed**: Reembolso completado
- **failed**: Reembolso falló
- **cancelled**: Reembolso cancelado

## ✅ Estado de Implementación

### ✅ Completado
- [x] Migraciones de base de datos
- [x] Modelos y relaciones
- [x] FlowService con todas las funcionalidades
- [x] Controladores de pago y reembolso
- [x] Rutas API configuradas
- [x] Validación de webhooks con HMAC
- [x] Manejo de errores y logs

### ⏳ Pendiente
- [ ] Componentes Angular frontend
- [ ] Tests unitarios
- [ ] Documentación de frontend
- [ ] Configuración de producción

## 🚀 Próximos Pasos

1. **Frontend Angular**: Crear componentes para:
   - Formulario de pago directo
   - Gestión de clientes Flow
   - Dashboard de pagos y reembolsos
   - Estados de transacciones

2. **Testing**: Implementar tests para:
   - Endpoints de API
   - Integración Flow.cl
   - Validación de webhooks

3. **Producción**: 
   - Configurar credenciales de producción
   - Verificar URLs de confirmación
   - Monitoreo y alertas