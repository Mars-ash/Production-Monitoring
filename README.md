# Production Monitoring (Laravel)

Project monitoring produksi berbasis Laravel.

## Setup lokal (Laragon / Windows)

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install
npm run dev
php artisan serve
```

## Deployment

Lihat panduan lengkap di `DEPLOYMENT.md`.
