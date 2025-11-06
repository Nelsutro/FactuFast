# cPanel Deployment Guide

This folder contains helper assets to deploy FactuFast to a shared-hosting
environment using cPanel's **Git Version Control** feature.

## 1. One-time server preparation

1. Log into your cPanel account and open **Git Version Control**.
2. Add the FactuFast repository (the same repository you manage locally).
3. After cloning finishes, click the repository entry and set a **Deployment
   Script** pointing to:

   ```bash
   /bin/bash $DEPLOYMENT_DIRECTORY/deploy/cpanel-deploy.sh
   ```

   `DEPLOYMENT_DIRECTORY` is the path displayed by cPanel for the cloned repo.
4. Ensure that:
   - PHP version selected in cPanel matches Laravel's requirement (`composer.json`).
   - Node.js Selector is set to Node 18 or newer (needed for the Angular build).
   - A database exists and credentials are added to `api/.env`.

## 2. Environment variables (.env)

The deployment script expects `api/.env` to exist with production settings.
If it does not exist on first deployment, the script will copy `.env.example`
and run `php artisan key:generate`. **You must immediately edit** `api/.env`
with real database/mail values before triggering the next deployment.

Key values to review:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` pointing to your domain/subdomain
- Database connection credentials (`DB_*`)
- Mail driver credentials if emails are needed

## 3. Document roots

- Laravel API lives inside `api/`. Its `public` directory must be exposed if
  you intend to serve the backend directly. A common approach is to point a
  subdomain (e.g., `api.yourdomain.com`) to `~/repositories/FactuFast/api/public`.
- Angular SPA is copied to `~/public_html` by default. Adjust the
  `PUBLIC_HTML` environment variable when creating the deployment script if you
  prefer a different destination (e.g., a subdomain folder).

## 4. Cron & queue workers

For scheduled jobs add a cron entry via cPanel:

```
* * * * * /usr/bin/php /home/<cpanel_user>/repositories/FactuFast/api/artisan schedule:run >> /home/<cpanel_user>/logs/laravel-scheduler.log 2>&1
```

If you use queues, configure a daemon (Application Manager or Supervisord) to
run:

```
php artisan queue:work --sleep=3 --tries=1 --timeout=90
```

## 5. Deployment Workflow

1. Push changes to the remote repository (GitHub, etc.).
2. In cPanel > Git Version Control, click **Pull or Deploy** for the repo.
3. cPanel runs `deploy/cpanel-deploy.sh` automatically, performing:
   - `composer install` with optimized autoloader
   - Permission fixes on `storage` and `bootstrap/cache`
   - `php artisan migrate --force`
   - Angular production build and copy to your `public_html`

## 6. Customization

If your hosting structure differs, override environment variables when setting
up the deploy command, for example:

```bash
ACCOUNT_HOME=/home/myuser \
PUBLIC_HTML=/home/myuser/public_html/app \
PHP_BIN=/opt/cpanel/ea-php82/root/usr/bin/php \
COMPOSER_BIN=/opt/cpanel/composer/bin/composer \
/bin/bash $DEPLOYMENT_DIRECTORY/deploy/cpanel-deploy.sh
```

Feel free to tailor the script to your needs (Clearing caches, restarting
processes, etc.).
