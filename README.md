# BRS Cloud

Backend SaaS de `BRS POS`.

Este repo es independiente de:

- `brs-code`: POS desktop, mobile, local-api y UI offline-first
- `brs-release`: releases, manifests y distribución de updates

Aqui vive:

- tenants
- sucursales
- dispositivos
- catálogo cloud compartido
- sync offline-first
- auth cloud con Sanctum
- base para suscripciones SaaS
- portal cloud del cliente
- consola interna de administracion BRS

## Arquitectura

### `brs-code`

Producto operativo local:

- Electron
- iPad / mobile
- SQLite local
- operación offline-first

### `brs-cloud`

Negocio SaaS y consolidación:

- tenants
- stores
- devices
- catálogo cloud
- sync de ventas/eventos
- entitlements
- suscripciones
- portal cloud para cliente
- admin console interna para BRS

### `brs-release`

Distribución de software:

- manifests
- paquetes
- canales
- releases por plataforma

## Stack

- `Laravel 13`
- `Laravel Sanctum`
- `MySQL` por default en local y producción
- `Cloudways`

## Modelo inicial

### Tenant

Cliente SaaS.

### Store

Sucursal o tienda.

### Device

Caja física: iPad, desktop o Android.

### Cloud catalog

Catálogo compartido por tienda con versión.

### Sync events

Eventos locales que se suben cuando hay conexión.

## Nomenclatura

En `brs-cloud` los contratos internos siguen usando `tenant` y `store`.

Eso aplica a:

- nombres de campos como `tenantName`, `storeName`, `storeCode`
- endpoints administrativos
- payloads de bootstrap y sync

Pero en el POS la interfaz visible ya muestra:

- `Negocio` en lugar de `Tenant`
- `Sucursal` en lugar de `Store`

La regla practica es:

- internals y API: `tenant/store`
- copy visible para operacion: `negocio/sucursal`

## Consola interna BRS

`brs-cloud` ahora tiene dos superficies distintas dentro del mismo Laravel:

- portal cloud del cliente
- consola interna `admin`

La separacion actual es por:

- layout distinto
- rutas `admin.*`
- middleware `platform.admin`
- bandera `users.is_platform_admin`
- host admin configurable con `APP_ADMIN_HOST`

Primera version disponible:

- si `APP_ADMIN_HOST` no esta definido:
  - `/admin`
  - `/admin/clients`
  - `/admin/clients/{tenant}`
  - `/admin/subscriptions`
- si `APP_ADMIN_HOST` esta definido:
  - `https://admin.tu-dominio.com/`
  - `https://admin.tu-dominio.com/clients`
  - `https://admin.tu-dominio.com/clients/{tenant}`
  - `https://admin.tu-dominio.com/subscriptions`

Desde esa consola puedes:

- ver todos los tenants/clientes
- revisar owner, plan, trial y estado
- ver sucursales, usuarios y actividad resumida
- editar `plan_code`
- editar `subscription_status`
- extender o quitar `trial_ends_at`
- activar o suspender un tenant

Por ahora, la “subscripcion” sigue leyendo y editando estos campos del tenant:

- `tenants.plan_code`
- `tenants.subscription_status`
- `tenants.trial_ends_at`
- `tenants.is_active`

Todavia no existe una tabla separada de billing como `subscriptions`.

### Habilitar un admin interno

Primero corre migraciones:

```bash
php artisan migrate --force
```

Luego marca manualmente un usuario como admin de plataforma:

```sql
update users
set is_platform_admin = 1
where email = 'tu-correo@dominio.com';
```

Ese usuario, al iniciar sesion, cae al dashboard interno `/admin`.

### Subdominio admin

Puedes servir el backoffice en un host distinto sin separar el proyecto Laravel.

Ejemplo:

```env
APP_URL=https://cloud.tu-dominio.com
APP_ADMIN_HOST=admin.tu-dominio.com
```

Con eso:

- las rutas `admin.*` se generan sobre `admin.tu-dominio.com`
- el login de un `is_platform_admin` redirige al host admin
- el fallback `/admin` solo redirige hacia el host admin

En infraestructura, ambos hosts pueden apuntar al mismo proyecto Laravel.

## Endpoints base

### Health

```http
GET /api/health
GET /api/cloud/health
```

### Login cloud con Sanctum

```http
POST /api/auth/login
```

Payload:

```json
{
  "email": "owner@bajaretailsystem.demo",
  "password": "demo1234"
}
```

### Usuario actual

```http
GET /api/auth/me
Authorization: Bearer <token>
```

### Logout

```http
POST /api/auth/logout
Authorization: Bearer <token>
```

### Token de dispositivo

```http
POST /api/cloud/device-token
X-BRS-Store-Code: MATRIZ-001
X-BRS-Store-Key: brs_demo_store_key_001
```

Payload:

```json
{
  "device_id": "ipad-front-01",
  "name": "Caja iPad 01",
  "platform": "ios",
  "device_type": "ipad",
  "app_mode": "mobile-local",
  "current_version": "1.0.0"
}
```

### Bootstrap de dispositivo

```http
POST /api/cloud/bootstrap
Authorization: Bearer <device-token>
```

### Snapshot de catálogo

```http
GET /api/cloud/catalog
Authorization: Bearer <device-token>
```

### Sync de eventos

```http
POST /api/cloud/sync/events
Authorization: Bearer <device-token>
```

## Flujo recomendado

1. El admin cloud hace login con Sanctum.
2. Registra tenant, store y branding.
3. El dispositivo pide `device-token` con `store code/key`.
4. El dispositivo usa ese token para `bootstrap`, `catalog` y `sync/events`.
5. La caja sigue operando offline y sincroniza cuando vuelve la red.

## Decisiones de arquitectura

- cada caja sigue operando localmente
- cloud consolida y redistribuye
- múltiples iPads pueden compartir catálogo
- el inventario entre cajas offline es de consistencia eventual
- las suscripciones viven a nivel tenant

## Seeder demo

El seeder crea:

- tenant demo: `Baja Retail System Demo`
- store demo: `MATRIZ-001`
- api key demo: `brs_demo_store_key_001`
- catálogo demo inicial
- usuario owner demo: `owner@bajaretailsystem.demo`
- password demo: `demo1234`

## Auth social

Se soporta acceso web con:

- `Google`
- `Apple`

Flujo:

- si el correo ya existe, el usuario entra y se vincula al provider
- si el correo no existe, se crea automaticamente:
  - `tenant`
  - `store` principal
  - `owner` inicial

Variables necesarias en `.env`:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://tu-dominio/auth/google/callback

GOOGLE_ADMIN_CLIENT_ID=
GOOGLE_ADMIN_CLIENT_SECRET=
GOOGLE_ADMIN_REDIRECT_URI=https://admin.tu-dominio.com/auth/google/callback

APPLE_CLIENT_ID=
APPLE_CLIENT_SECRET=
APPLE_REDIRECT_URI=https://tu-dominio/auth/apple/callback
APPLE_TEAM_ID=
APPLE_KEY_ID=

APPLE_ADMIN_CLIENT_ID=
APPLE_ADMIN_CLIENT_SECRET=
APPLE_ADMIN_REDIRECT_URI=https://admin.tu-dominio.com/auth/apple/callback
APPLE_ADMIN_TEAM_ID=
APPLE_ADMIN_KEY_ID=
```

Importante:

- `Apple` puede regresar al callback por `POST`, por eso la ruta acepta `GET` y `POST`
- en Cloudways debes registrar exactamente esos redirects en Google Cloud Console y Apple Developer

## OAuth por superficie

`brs-cloud` puede usar clientes OAuth distintos para:

- portal cliente `venpi.mx`
- admin console `admin.venpi.mx`

Comportamiento:

- si el request entra por el host admin, usa `GOOGLE_ADMIN_*` o `APPLE_ADMIN_*`
- si esas variables no estan definidas, hace fallback a `GOOGLE_*` y `APPLE_*`
- si el request entra por el host principal, usa los clientes normales

Configuracion recomendada en Google:

- Cliente OAuth 1: `BRS Cloud Web`
  - origin: `https://venpi.mx`
  - redirect: `https://venpi.mx/auth/google/callback`
- Cliente OAuth 2: `BRS Admin Web`
  - origin: `https://admin.venpi.mx`
  - redirect: `https://admin.venpi.mx/auth/google/callback`

Configuracion equivalente sugerida en `.env`:

```env
APP_URL=https://venpi.mx
APP_ADMIN_HOST=admin.venpi.mx

GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://venpi.mx/auth/google/callback

GOOGLE_ADMIN_CLIENT_ID=...
GOOGLE_ADMIN_CLIENT_SECRET=...
GOOGLE_ADMIN_REDIRECT_URI=https://admin.venpi.mx/auth/google/callback

SESSION_DOMAIN=.venpi.mx
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

## Desarrollo local

```bash
composer install
cp .env.example .env
php artisan key:generate
# configura tu MySQL local o Cloudways en .env
php artisan migrate:fresh --seed
php artisan serve
```

## Deploy manual en Cloudways

Despues de `git pull`, el flujo manual recomendado para este repo es:

```bash
cd /Users/marcelcelaya/Documents/BRS-workspace/brs-cloud
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan view:cache
```

Si quieres dejar rutas cacheadas tambien:

```bash
php artisan route:cache
```

Si hay workers/colas corriendo en produccion:

```bash
php artisan queue:restart
```

Notas:

- `migrate --force` debe correr en deploy productivo
- `optimize:clear` ya limpia caches previos; los `clear` de abajo se mantienen porque asi ha quedado tu rutina manual de deploy
- el POS ya consume copy visible `Negocio/Sucursal`, pero los contratos del backend siguen siendo `tenant/store`

## Siguiente paso recomendado

1. `Laravel Cashier + Stripe`
2. versionado formal de catálogo
3. jobs de sync
4. panel admin cloud multi-sucursal por tenant/store/device
5. auditoría y métricas de sincronización
