<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Configuración de SMTP (envío de correos)

Para habilitar el envío de correos (por ejemplo, enviar facturas por email), configura las variables en tu archivo `.env` dentro de la carpeta `api/`:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.tu-proveedor.com
MAIL_PORT=587
MAIL_USERNAME=tu_usuario
MAIL_PASSWORD=tu_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@tudominio.com
MAIL_FROM_NAME="FactuFast"
```

Notas:
- Usa los datos reales de tu proveedor (Gmail, Outlook, tu hosting, etc.).
- Para Gmail, puede requerirse crear una contraseña de aplicación y usar `MAIL_ENCRYPTION=tls` con puerto 587.
- En desarrollo, puedes utilizar [Mailhog](https://github.com/mailhog/MailHog) o servicios como Mailtrap. Cambia `MAIL_HOST`, `MAIL_PORT` y credenciales acorde.
- Tras modificar `.env`, limpia cachés si es necesario:
	- `php artisan config:clear`
	- `php artisan cache:clear`

Una vez configurado, el endpoint `POST /api/invoices/{invoice}/email` podrá enviar correos usando estas credenciales.

## Pasarela Webpay (Transbank)

El backend requiere credenciales de Webpay Plus REST para iniciar transacciones desde el portal de clientes. Declara en tu `.env` (carpeta `api/`) los siguientes campos:

```
WEBPAY_ENVIRONMENT=integration   # integration | production
WEBPAY_COMMERCE_CODE=597055555532
WEBPAY_API_KEY=tu_api_key_de_transbank
```

Significado de cada variable:

- **WEBPAY_ENVIRONMENT**: ambiente que usará la integración. Usa `integration` con las credenciales de sandbox y cambia a `production` cuando Transbank habilite el comercio real.
- **WEBPAY_COMMERCE_CODE**: código de comercio asignado por Transbank para Webpay Plus REST. Debe coincidir con el ambiente elegido.
- **WEBPAY_API_KEY**: API Key secreta asociada a ese código de comercio. Transbank la entrega en el portal de comercios.

Cómo se utilizan:

- El servicio `WebpayGateway` lee estos valores cuando se construye desde la configuración de cada empresa. Puedes cargar las credenciales directamente en la tabla `companies` (campos `webpay_environment`, `webpay_commerce_code`, `webpay_api_key`) o usarlos como valores por defecto al crear una empresa.
- Si alguno de los campos es incorrecto o está vacío, Transbank responderá con `Not Authorized` al iniciar la transacción y el cliente no podrá completar el pago.

Recomendaciones:

- Prueba primero con las credenciales oficiales de integración para validar el flujo end-to-end.
- Al cambiar datos sensibles en `.env`, ejecuta `php artisan config:clear` para refrescar la caché de configuración.
- En producción, guarda las credenciales reales (no las de integración) y limita el acceso al archivo `.env`.

## Autenticación OAuth (Google, Microsoft y Apple)

Para habilitar el inicio de sesión social, configura las credenciales de cada proveedor y habilita las rutas expuestas bajo `/api/auth/oauth/*`.

### Variables de entorno

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/oauth/google/callback"

MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=
MICROSOFT_TENANT_ID=common
MICROSOFT_REDIRECT_URI="${APP_URL}/auth/oauth/microsoft/callback"

APPLE_CLIENT_ID=
APPLE_TEAM_ID=
APPLE_KEY_ID=
APPLE_PRIVATE_KEY='-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----'
APPLE_REDIRECT_URI="${APP_URL}/auth/oauth/apple/callback"

OAUTH_ALLOWED_REDIRECTS=http://localhost:4200/oauth/callback,https://app.factufast.com/oauth/callback
```

> **Importante:** el paquete `socialiteproviders/apple` depende de la extensión `ext-sodium`. Activa la extensión en tu `php.ini` o instala la librería `libsodium`. En entornos donde no sea posible, instala el paquete pero no podrás completar el flujo de Apple hasta habilitarla.

### Dependencias instaladas

- `laravel/socialite` v5.23: núcleo de OAuth.
- `socialiteproviders/microsoft` v4.7: driver para Microsoft Entra ID.
- `socialiteproviders/apple` v5.7: driver para Sign in with Apple.

### Migraciones nuevas

- `social_accounts`: almacena la relación usuario ↔ proveedor (tokens encriptados, avatar, expiración, etc.).
- `oauth_states`: conserva tokens `state` firmados para validar los flujos sin sesiones de servidor.

Ejecuta `php artisan migrate` tras actualizar el código para aplicar estas tablas.

### Flujo soportado

1. El frontend pide a `/api/auth/oauth/{provider}/redirect` la URL de autenticación.
2. Laravel genera un registro `oauth_states`, firma el estado y devuelve la URL del proveedor.
3. El usuario se autentica con el proveedor y vuelve al callback `/api/auth/oauth/{provider}/callback`.
4. El backend valida el `state`, enlaza la cuenta social, crea el usuario si no existe y emite un token Sanctum.
5. El backend redirige al frontend (`/oauth/callback`) con `status`, `token`, `provider`, `state` y `return_url`.

### Configuración adicional

- Añade todos los orígenes del frontend en `OAUTH_ALLOWED_REDIRECTS` para evitar errores de redirección.
- Para Apple, registra una clave privada en formato `.p8` y cópiala en `APPLE_PRIVATE_KEY` (respetando los saltos `\n`).
- El frontend espera recibir el token en la ruta `/oauth/callback`, por lo que asegúrate de que esa URL esté incluida en las listas de redirect de cada proveedor.

## Scheduler (Tareas programadas)

Este proyecto usa el scheduler de Laravel para automatizaciones periódicas.

Tareas incluidas en el MVP:
- Marcar facturas como vencidas: cambia `status` de `pending` a `overdue` cuando `due_date` < hoy.
- Marcar cotizaciones como expiradas: cambia `status` de `draft`/`sent` a `expired` cuando `expiry_date` < hoy.

Cómo ejecutarlo en desarrollo:

```bat
# Desde la carpeta api/
php artisan schedule:work
```

Esto dejará un worker en primer plano que ejecuta las tareas según el cron interno (en este caso, cada 5 minutos).

Ejecución manual (útil para probar):

```bat
php artisan automation:run --dry-run  # Muestra qué haría, sin aplicar cambios
php artisan automation:run           # Ejecuta y aplica cambios
```

Configuración en producción (Windows Task Scheduler o cron en Linux):
- Cron (Linux): agregar una entrada que ejecute cada minuto el scheduler de Laravel:

```bash
* * * * * cd /ruta/a/api && php artisan schedule:run >> /dev/null 2>&1
```

- Windows (Task Scheduler): crear una tarea programada que ejecute cada 1 minuto:
	- Programa: `php`
	- Argumentos: `artisan schedule:run`
	- Directorio de inicio: ruta a la carpeta `api` del proyecto

Ver logs:
- Los mensajes se escriben en consola al ejecutar los comandos.
- Para ver cambios aplicados en datos, revisa la BD o endpoints de facturas/cotizaciones.

Personalización futura:
- Se puede ampliar con nuevas tareas (p. ej. generación de borradores recurrentes) y administrar `schedules` por empresa.

## Acceso por RUT (empresas y portal de clientes)

Empresas (usuarios):
- Registro: enviar `company_tax_id` junto con `company_name`, `name`, `email`, `password`. El backend crea o asocia la `Company` por `tax_id` y guarda `company_id` en el usuario.
- Login: el endpoint acepta opcionalmente `tax_id`; si se envía, se valida que coincida con `companies.tax_id` del usuario.

Portal de clientes:
- Solicitud de acceso: `POST /api/client-portal/request-access` con `email` y opcional `company_tax_id`. Se valida que el cliente pertenezca a esa empresa si se envía.
- Acceso con token: `POST /api/client-portal/access` con `email`, `token` y opcional `company_tax_id`.

Notas:
- El campo `companies.tax_id` es único (RUT). En `clients` también existe `tax_id` (opcional) para identificar clientes por RUT si se requiere en integraciones futuras.

## Notificaciones por correo (facturas y pagos)

Eventos notificados (si hay correos disponibles):
- Factura creada: se envía email al cliente y a la empresa.
- Pago recibido: se envía comprobante al cliente y notificación a la empresa.

Puntos de enganche:
- Facturas: `InvoiceController@store` (después de crear y cargar relaciones) envía `InvoiceCreatedClientMail` y `InvoiceCreatedCompanyMail`.
- Pagos: `PaymentController@store` envía `PaymentReceivedClientMail` y `PaymentReceivedCompanyMail`.

Requisitos:
- Configurar SMTP en `.env` (ver sección anterior). Si hay errores al enviar, se registran en logs y no interrumpen la operación principal.
