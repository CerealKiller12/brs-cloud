# BRS Cloud

Backend SaaS de BRS POS.

Este repo es independiente de:

- `brs-code`: POS desktop, mobile, local-api y UI
- `brs-release`: releases, manifests y distribución de updates

Aqui vive:

- tenants
- sucursales
- dispositivos
- catálogo cloud compartido
- sync offline-first
- base para suscripciones SaaS
- backoffice cloud

## Rol dentro del sistema

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
- suscripciones
- entitlements
- backoffice

### `brs-release`

Distribución de software:

- manifests
- paquetes
- canales
- releases por plataforma

## Stack

- `Laravel 13`
- `MySQL`
- `Cloudways`

## Endpoints cloud base

### Health

```http
GET /api/health
GET /api/cloud/health
```

### Bootstrap de dispositivo

```http
POST /api/cloud/bootstrap
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

### Snapshot de catálogo

```http
GET /api/cloud/catalog
X-BRS-Store-Code: MATRIZ-001
X-BRS-Store-Key: brs_demo_store_key_001
```

### Sync de eventos

```http
POST /api/cloud/sync/events
X-BRS-Store-Code: MATRIZ-001
X-BRS-Store-Key: brs_demo_store_key_001
```

## Decisión de arquitectura

- cada caja sigue operando localmente
- el cloud consolida y redistribuye
- múltiples iPads pueden compartir catálogo
- el inventario entre cajas offline es de consistencia eventual
- las suscripciones viven a nivel tenant

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

## Seeder demo

El seeder crea:

- tenant demo: `Baja Retail System Demo`
- store demo: `MATRIZ-001`
- api key demo: `brs_demo_store_key_001`
- catálogo demo inicial

## Desarrollo local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Siguiente paso recomendado

1. `Laravel Sanctum`
2. `Laravel Cashier + Stripe`
3. versionado formal de catálogo
4. jobs de sync
5. panel admin cloud por tenant/store/device
