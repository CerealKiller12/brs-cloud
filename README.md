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
- `MySQL` en producción
- `SQLite` para desarrollo local
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

## Desarrollo local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

## Siguiente paso recomendado

1. `Laravel Cashier + Stripe`
2. versionado formal de catálogo
3. jobs de sync
4. panel admin cloud por tenant/store/device
5. auditoría y métricas de sincronización
