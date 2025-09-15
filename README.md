# FactuFast

Guía rápida para correr el backend (Laravel) y el frontend (Angular), y cómo configurar un VirtualHost en XAMPP.

## Requisitos previos

- PHP 8.2+ (recomendado), Composer instalado y disponible en la terminal
- Node.js 18+ y npm 8+ (o superior)
- Angular CLI instalado globalmente (opcional pero recomendado): `npm install -g @angular/cli`
- XAMPP (opcional, solo si deseas montar un VirtualHost con Apache en local)

Estructura del repo:
- `api/` → Backend Laravel
- `site/` → Frontend Angular

## Backend (Laravel) 🚀

1) Instalar dependencias
- Abrir una terminal en la carpeta `api/` y ejecutar:

```
composer install
```

2) Configurar variables de entorno
- Copiar `.env.example` a `.env` y ajustar según tu entorno (APP_URL, DB_*, MAIL_*, etc.). Si usas SQLite (ya hay `database/database.sqlite`), asegúrate de que el path coincide.

```
cp .env.example .env
```

3) Generar APP_KEY y migrar/sembrar base de datos

```
php artisan key:generate
php artisan migrate --seed
```

4) Levantar servidor de desarrollo

```
php artisan serve --host=127.0.0.1 --port=8000
```

- API base por defecto: http://127.0.0.1:8000/api
- Endpoints de autenticación (Sanctum): `POST /api/auth/login`, `POST /api/auth/register`, etc.

Notas:
- Si usas XAMPP/Apache como servidor, puedes omitir `artisan serve` y apuntar un VirtualHost a `api/public` (ver guía más abajo).

## Frontend (Angular) 🌐

1) Instalar dependencias
- Abrir una terminal en la carpeta `site/` y ejecutar:

```
npm install
```

2) Configurar URL del backend
- El frontend usa `environment.apiUrl`. Por defecto, suele apuntar a `http://127.0.0.1:8000/api`. Ajusta si cambiaste host/puerto o si montas un VirtualHost.

3) Correr servidor de desarrollo

```
npm start
```

- Por defecto (Angular dev server): http://localhost:4200

4) Build de producción (opcional)

```
npm run build
```

## Variables de entorno esperadas en el Frontend

- `environment.apiUrl` debe apuntar al backend Laravel, por ejemplo:
  - `http://127.0.0.1:8000/api` (usando `php artisan serve`)
  - `http://factufast.local/api` (si usas VirtualHost con XAMPP)

Si no encuentras el archivo de entorno, puedes crearlo (Angular 15+ standalone) o adaptar la referencia en `site/src/app/environments/` según tu configuración.

## Configurar VirtualHost en XAMPP (Apache) 🛠️

Objetivo: Servir el backend Laravel desde un dominio local, por ejemplo `http://factufast.local` apuntando a `api/public`.

1) Editar `hosts` del sistema
- Abrir como administrador el archivo:
  - Windows: `C:\Windows\System32\drivers\etc\hosts`
- Agregar una línea:

```
127.0.0.1    factufast.local
```

2) Crear el VirtualHost en Apache
- Abrir `httpd-vhosts.conf` (en XAMPP suele estar en `C:\xampp\apache\conf\extra\httpd-vhosts.conf`).
- Agregar la configuración:

```
<VirtualHost *:80>
    ServerName factufast.local
    DocumentRoot "C:/ruta/a/tu/proyecto/FactuFast/api/public"

    <Directory "C:/ruta/a/tu/proyecto/FactuFast/api/public">
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/factufast-error.log"
    CustomLog "logs/factufast-access.log" common
</VirtualHost>
```

- Ajusta la ruta absoluta a donde está el repo en tu máquina.

3) Habilitar `mod_rewrite` (si no lo está)
- En `httpd.conf`, asegúrate de que esta línea no esté comentada:

```
LoadModule rewrite_module modules/mod_rewrite.so
```

4) Reiniciar Apache
- Desde el panel de XAMPP, detener y volver a iniciar Apache.

5) Probar
- Abre `http://factufast.local` en el navegador. Debe resolver a Laravel (ruta pública `api/public`).
- La API quedará disponible en `http://factufast.local/api`.

6) Apuntar el Frontend a ese dominio
- Ajusta `environment.apiUrl` en Angular a `http://factufast.local/api`.

## Problemas comunes y soluciones 🔧

- 403/401 desde el frontend: verifica que el token se esté enviando (Auth header) y que el dominio coincida con el backend configurado (CORS). En Laravel, ajusta `config/cors.php` si es necesario y limpia cachés (`php artisan config:clear`).
- 500 en migraciones: revisa permisos del archivo `database/database.sqlite` (si usas SQLite) o credenciales de DB en `.env`.
- 404 con VirtualHost: confirma que `DocumentRoot` apunta a `api/public` y que `AllowOverride All` esté activo para que `.htaccess` funcione.
- CORS en desarrollo: si sirves Angular en `http://localhost:4200`, y backend en `http://127.0.0.1:8000`, agrega ambos orígenes a CORS si hace falta.

## Comandos útiles (resumen)

Backend (en `api/`):
```
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Frontend (en `site/`):
```
npm install
npm start
```

XAMPP (Apache):
- Editar `hosts` y `httpd-vhosts.conf`, habilitar `mod_rewrite`, reiniciar Apache.

---

¿Quieres que prepare también un archivo `.env` de ejemplo adaptado para SQLite y endpoints locales, y que deje `environment.ts` apuntando a tu VirtualHost? Puedo hacerlo en un siguiente paso.