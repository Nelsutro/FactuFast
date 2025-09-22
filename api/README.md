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
