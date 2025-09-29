# Plan de Arquitectura SSO (OAuth 2.0)

## Objetivos

- Permitir a los usuarios autenticarse con Google, Microsoft y Apple desde el portal de empresas (SPA Angular).
- Mantener compatibilidad con el login por email/contraseña existente.
- Emitir tokens Sanctum tras un inicio de sesión exitoso para unificar el manejo de sesiones.
- Registrar las vinculaciones proveedor ↔ usuario para futuras conexiones/desconexiones.
- Garantizar flujos seguros en un entorno sin sesiones de servidor (API stateless).

## Flujo resumido

1. **Angular** solicita al backend la URL de autorización (`GET /auth/oauth/{provider}/redirect?redirect_uri=...`).
2. **Laravel** genera un registro `oauth_states` (token de estado, proveedor, redirect) y devuelve una respuesta JSON con la URL completa hacia el proveedor.
3. **Angular** abre la URL en una nueva pestaña/ventana.
4. El **proveedor OAuth** autentica al usuario y redirige al backend (`/auth/oauth/{provider}/callback?code=...&state=...`).
5. **Laravel** valida el `state`, recupera/crea el usuario y devuelve un `redirect` hacia el frontend (`redirect_uri`) con un token Sanctum y metadatos (`?token=...&status=success`).
6. **Angular** captura el token en la ruta `oauth/callback`, guarda el token y cierra la ventana emergente o redirige al dashboard.

> Para tratar los casos de error (cancelación, credenciales inválidas, etc.) el backend redirigirá con `status=error` y un mensaje descriptivo.

## Componentes backend

- **Dependencias**
  - `laravel/socialite` para el flujo OAuth base.
  - `socialiteproviders/microsoft` y `socialiteproviders/apple` para proveedores adicionales.
- **Config**
  - Variables de entorno `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, etc.
  - Entradas en `config/services.php` para cada proveedor.
- **Modelo `SocialAccount`**
  - Campos: `user_id`, `provider`, `provider_id`, `email`, `avatar`, `access_token`, `refresh_token`, `token_expires_at`, `profile_raw`.
  - Relación `belongsTo(User)`.
- **Tabla `oauth_states`**
  - Campos: `state`, `provider`, `redirect_uri`, `expires_at`, `consumed_at`, `payload` (json).
  - Permite validar la integridad del flujo sin utilizar sesiones.
- **Controlador `OAuthController`**
  - `redirect()` crea el estado y devuelve URL.
  - `callback()` procesa la respuesta del proveedor, enlaza/crea usuario, emite token y prepara la redirección al frontend.
- **Servicio `OAuthService`** (opcional pero recomendado)
  - Encapsula la lógica de resolución de proveedores, vinculación de cuentas, creación de tokens y manejo de estados.

## Componentes frontend

- **Servicio `OauthService`**
  - `getRedirectUrl(provider)` → `GET /auth/oauth/{provider}/redirect`.
  - `finalizeLogin(payload)` → procesa token/estado recibidos.
- **Componente `oauth-callback`**
  - Ruta `/#/oauth/callback` o `/oauth/callback` dentro de Angular.
  - Lee parámetros `status`, `token`, `message` desde la URL (y `state` opcional).
  - Si `status=success`, guarda token (`AuthService`) y redirige.
- **Botones de SSO en el login**
  - Reutilizan `OauthService` y abren la ventana emergente.
  - Muestran estados (`loading`, `error`, `success`).

## Seguridad

- **State con HMAC**: el token de estado incluirá una firma (`hash_hmac`) utilizando `APP_KEY` para evitar manipulaciones.
- **Expiración corta**: registros `oauth_states` caducan a los 5 minutos.
- **Campos sensibles cifrados**: `access_token`, `refresh_token` almacenados mediante `encrypt()`.
- **Restricción de redirect**: sólo permitir `redirect_uri` presentes en `config('oauth.allowed_redirects')` o el dominio oficial del frontend.
- **Scopes mínimos**: solicitar scopes básicos (`email`, `profile`).
- **Sin sesión**: siempre usar `Socialite::driver(...)->stateless()`.
- **Apple requiere `ext-sodium`**: habilita la extensión de PHP o instala `libsodium` para firmar los `client_secret` de Apple.

## Experiencia de usuario

- Si el usuario existe (por email), el flujo inicia sesión y vincula el proveedor si no estaba enlazado.
- Si es nuevo, se crea usuario con rol `client` por defecto y se marca con flag `onboarding_pending` para solicitar datos faltantes.
- Si la cuenta ya está vinculada a otro usuario, se informará con `status=error` y se ofrecerá iniciar sesión con ese usuario.

## Próximos pasos

1. Instalar dependencias mediante Composer.
2. Crear migraciones para `social_accounts` y `oauth_states`.
3. Implementar `OAuthController` y rutas en `routes/api.php`.
4. Añadir métodos auxiliares en `User` y `AuthService`.
5. Construir el servicio y componentes Angular.
6. Documentar configuración en README (`api` y `site`).
7. Probar el flujo completo en el entorno de integración de cada proveedor.
