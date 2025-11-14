# 🚀 Production Deployment Guide

## Railway Deployment

### Automatic Setup (Recommended)

Railway akan otomatis menjalankan commands berikut saat deploy (dari `railway.toml`):

```bash
npm install --legacy-peer-deps --omit=dev
npm run build
composer install --no-dev
php artisan migrate --force
php artisan db:seed --force
php artisan app:generate-placeholder-blobs --force
php artisan app:normalize-blob-records
```

### Manual Setup (jika diperlukan)

Jika ingin menjalankan ulang commands setelah deploy:

```bash
# SSH ke Railway container
railway shell

# Jalankan migrasi BLOB
php artisan app:generate-placeholder-blobs --force
php artisan app:normalize-blob-records

# Atau migrate dari filesystem ke BLOB (jika file ada)
php artisan app:migrate-missing-blobs-from-path

# Check status
php artisan app:migrate-bukti-to-blob
```

### Docker Setup

Jika menggunakan Docker locally atau di environment lain:

```bash
docker build -t ukk-app .
docker run -p 8080:8080 ukk-app
```

Dockerfile akan otomatis:
1. Build Node assets
2. Install PHP + Composer dependencies
3. Run migrations & seeders
4. Fill missing BLOBs with placeholders
5. Normalize BLOB records

## BLOB Management Commands

### 1. Generate Placeholder BLOBs

Untuk fill semua records yang missing BLOB dengan placeholder image:

```bash
php artisan app:generate-placeholder-blobs --force
```

### 2. Normalize BLOB Records

Untuk memastikan semua BLOB records punya metadata lengkap (mime, name, size):

```bash
php artisan app:normalize-blob-records
```

### 3. Migrate dari Filesystem ke BLOB

Jika file masih ada di storage (belum hilang), copy ke BLOB:

```bash
php artisan app:migrate-missing-blobs-from-path
```

### 4. Migrate Existing Files

Jika punya records dengan bukti_pembayaran tetapi belum di-BLOB:

```bash
php artisan app:migrate-bukti-to-blob --force
```

### 5. Check Status

Check BLOB status untuk specific record:

```bash
php scripts/check_blob_by_filename.php <filename>
php scripts/check_latest_uploads.php
```

## Environment Variables (Railway)

Pastikan environment variables sudah set di Railway dashboard:

```
APP_NAME=UKK_NEW
APP_ENV=production
APP_KEY=base64:xxx...
APP_DEBUG=false
APP_URL=https://your-railway-domain.com

DB_CONNECTION=mysql
DB_HOST=<railway-mysql-host>
DB_PORT=3306
DB_DATABASE=<database-name>
DB_USERNAME=<username>
DB_PASSWORD=<password>

FILESYSTEM_DISK=public
```

## Image Serving

Semua bukti pembayaran sekarang di-serve dari database BLOB:

- **Route**: `/pembayaran/bukti/{filename}` - fallback by filename
- **Primary**: `/pembayaran/bukti/blob/{id}` - serve by ID (lebih reliable)

Model accessor otomatis return BLOB route dalam view.

## Troubleshooting

### Images tidak muncul setelah deploy?

1. Check apakah BLOB population command sudah jalan:
   ```bash
   railway shell
   php artisan app:fill-missing-blobs-placeholder --force
   ```

2. Check AppServiceProvider sedang jalan (auto-populate pada setiap request)

3. Verify database connection dari Railway dapat

### File terasa lambat?

BLOB dilayani dengan cache headers (3600 seconds):

```php
Cache-Control: public, max-age=3600
```

Refresh browser atau clear cache jika ingin lihat update baru.

## Next Steps

1. ✅ Deploy ke Railway dengan `railway.toml`
2. ✅ Verify di production: open `/pembayaran/bukti/{filename}`
3. ✅ Jika ada masalah, check logs: `railway logs`
4. ✅ Atau SSH: `railway shell` dan run commands manual

---

**Last Updated**: 2025-11-14
**Commands Version**: 1.0
