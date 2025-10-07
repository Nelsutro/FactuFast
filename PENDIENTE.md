# Plan de acción de pendientes

Este documento consolida el estado actual del cronograma y define los pasos siguientes para cada bloque funcional de FactuFast.

## Prioridades inmediatas

### Recepción masiva (lotes)
- [x] Implementar cola de procesamiento y workers para dividir los CSV en lotes.
- [x] Guardar bitácoras por registro con estado (ok/error) descargables desde la UI.
- [x] Añadir reintentos automáticos y métricas básicas de carga en el dashboard.
- [x] Surfacear métricas de lotes en la SPA (cards + historial) y alertas básicas.
- [ ] Conectar alertas de importación con notificaciones (snackbar/correo) y filtros de histórico.

### Recepción vía API
- [x] Emitir claves API por cliente (tabla de tokens + consola de administración).
- [ ] Documentar oficialmente los endpoints (OpenAPI y guía rápida) y limitar scopes por token.
- [x] Configurar rate limiting y auditoría de uso por token.
- [x] Exponer reporte básico de uso (endpoint/tabla) y panel mínimo en la UI.
- [ ] Diseñar alertas y filtros avanzados para tokens (errores recurrentes, umbrales de consumo).

### Programación automática
- Extender el cron actual para generar facturas/recordatorios según reglas configurables.
- UI mínima para definir reglas (frecuencia, plantilla base).
- Alertas automáticas si el job falla (logs + notificación a administradores).

### Login Google/Microsoft/Apple
- Registrar apps en cada proveedor y cargar credenciales en `.env`.
- Probar callback real en staging y documentar el alta de credenciales.
- QA end-to-end con la SPA y dispositivos móviles, incluyendo la vinculación de cuentas existentes.

### Pago tarjeta/débito y confirmación automática
- Completar el flujo Webpay (redirect, return, commit) y reflejar estados en la UI pública.
- Agregar listeners específicos por proveedor a los webhooks y gestionar alertas ante discrepancias.
- Añadir pruebas automatizadas para estados clave (pending/paid/failed).

## Corto plazo

### Generación semanal/quincenal/mensual
- Diseñar motor de plantillas con variables de factura/cotización.
- Crear UI para administrar plantillas y asignarlas a clientes o grupos.
- Integrar con la programación automática y permitir simulaciones previas a la emisión.

### Aceptación automática → factura
- Crear flujo público protegido para que el cliente acepte/rechace con token único.
- (Opcional) Agregar firma digital simple vía OTP/email mientras llega la solución robusta.
- Convertir automáticamente la cotización aceptada en factura y disparar notificaciones.

### Recordatorios inteligentes
- Servicio de notificaciones con colas (mail, webhook y futuro SMS/WhatsApp).
- Plantillas editables y reglas (antes/después del vencimiento, seguimiento).
- Historial de recordatorios enviado por factura.

### Portal cliente con historial
- Añadir secciones de cotizaciones, descargas (PDF/XML) y filtros avanzados.
- Branding configurable por empresa y soporte multi-idioma como piloto (ES/EN).
- Métricas de interacción (último acceso, descargas por documento).

### Pago transferencia/billeteras
- Integrar MercadoPago real (checkout preferencial + status y webhooks).
- Evaluar link de transferencia directa con conciliación manual asistida.
- Permitir subir comprobantes y registrar evidencias de pago.

## Mediano plazo

### Dashboard inteligente
- Definir métricas avanzadas (cohortes, aging, forecast de ingresos).
- Integrar librería de visualización con filtros dinámicos y segmentaciones.
- Preparar endpoints optimizados y caching por empresa.

### Firma digital
- Evaluar proveedores (firma simple vs avanzada) e integración con SII.
- Diseñar flujo de solicitud, sellado y almacenamiento seguro de firmas.
- Adaptar plantillas de documentos para mostrar sellos y metadatos de firma.

### Integración bancaria/contable
- Priorizar bancos/ERPs objetivo (ej. conciliación BCI, integración Nubox/Softland).
- Diseñar conectores modulares y pipelines de conciliación.
- Auditoría detallada y monitoreo de sincronizaciones.

### Facturación recurrente / suscripciones
- Completar el modelo `Schedule` con planes, ciclos y promociones.
- Automatizar cargos vía Webpay/MercadoPago, con manejo de fallas y reintentos.
- Dashboard de suscripciones, churn y estado de cobros.

### IA / OCR
- Prototipo con servicio OCR (Azure Form Recognizer, Google Vision) para leer documentos.
- Pipeline de deduplicación y enriquecimiento de datos.
- Interfaz para revisión manual y entrenamiento incremental.

### Multi-idioma / multi-moneda
- Internacionalización en Angular (i18n) y Laravel (Lang).
- Conversión cambiaria con tasas actualizadas y configuración por empresa.
- Ajustar reportes, PDFs y correos a la moneda e idioma seleccionados.

## Soporte transversal
- Documentar cada entrega (docs, changelog y PENDIENTE.md).
- Añadir pruebas end-to-end donde corresponda (Cypress/Playwright).
- Monitoreo y alertas para jobs críticos (recepción masiva, pagos, recordatorios).
- Mantener el cronograma en la herramienta de gestión (Jira/Linear) con dependencias y responsables.
