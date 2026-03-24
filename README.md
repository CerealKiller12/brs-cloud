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
- backoffice cloud

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
- backoffice

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

APPLE_CLIENT_ID=
APPLE_CLIENT_SECRET=
APPLE_REDIRECT_URI=https://tu-dominio/auth/apple/callback
APPLE_TEAM_ID=
APPLE_KEY_ID=
```

Importante:

- `Apple` puede regresar al callback por `POST`, por eso la ruta acepta `GET` y `POST`
- en Cloudways debes registrar exactamente esos redirects en Google Cloud Console y Apple Developer

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
