# Despliegue Backend - Ambiente de Certificación

1. Copia `.env.cert.example` a `.env` y completa las credenciales reales (base de datos, Redis, SMTP, Webpay, MercadoPago y OAuth).
2. Instala dependencias en el servidor:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   ```
3. Ejecuta migraciones (sin volver a sembrar si la base ya contiene datos):
   ```bash
   php artisan migrate --force
   ```
4. Limita cachés y enlaza storage:
   ```bash
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   ```
5. Configura el worker de colas y el scheduler:
   ```bash
   php artisan queue:work --daemon
   # Configura cron para `php artisan schedule:run` cada minuto.
   ```
6. Verifica logs en `storage/logs/laravel.log` y endpoints críticos (`/api/health`, `/api/auth/login`) antes de abrir tráfico.
