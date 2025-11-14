# Storage Setup untuk Railway

Dokumen ini menjelaskan cara mensetup file uploads (bukti pembayaran) agar berfungsi dengan baik di Railway.

## Masalah di Railway

Railway menggunakan ephemeral filesystem, yang artinya:
- File yang diupload tidak bertahan setelah deployment ulang.
- Symlink dari `public/storage` ke `storage/app/public` mungkin tidak tersedia.

Solusi kami:
1. **Controller Route** (`/pembayaran/bukti/{filename}`): Melayani file melalui Laravel app, tidak bergantung pada symlink.
2. **Storage Disk**: Menggunakan Laravel Storage API untuk baca/tulis yang reliable.
3. **Fallback Mechanisms**: Jika symlink tidak ada, tetap bisa mengakses file via route.

---

## Cara Setup di Railway

### 1. Deploy dan Jalankan Migration

```bash
# Setelah push ke GitHub, Railway akan automatic build
# Ensure migration run:
php artisan migrate
```

### 2. Setup Storage Link (Opsi A - Direkomendasikan)

Jalankan command bawaan Laravel:

```bash
php artisan storage:link
```

Atau gunakan custom command kami yang lebih robust:

```bash
php artisan storage:setup
```

Command ini akan:
- Membuat directory `storage/app/public/bukti_pembayaran` jika belum ada.
- Membuat symlink dari `public/storage` → `storage/app/public`.
- Bekerja di Railway bahkan jika symlink fail (aplikasi tetap berfungsi via controller route).

### 3. Konfigurasi Railway.toml (Otomatis Setup)

Tambahkan ke file `railway.toml` di root project:

```toml
[build]
builder = "paketo"
buildpacks = ["gcr.io/paketo-buildpacks/php"]

[deploy]
startCommand = "php artisan serve --host 0.0.0.0 --port $PORT"
restartPolicyMaxRetries = 0

[[services.web.run.cmd]]
# Run migrations and setup storage
cmd = "php artisan migrate --force && php artisan storage:setup"
```

**Atau gunakan Procfile** (sudah ada di project):

```
# Procfile
web: composer install && php artisan migrate --force && php artisan storage:setup && php-server public/index.php
```

### 4. Environment Variables di Railway

Pastikan variabel berikut set di Railway:

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generated-by-laravel>
DB_CONNECTION=mysql
DB_HOST=<railway-db-host>
DB_PORT=3306
DB_DATABASE=<railway-db-name>
DB_USERNAME=<railway-db-user>
DB_PASSWORD=<railway-db-password>
```

---

## Cara Upload dan Akses Bukti Pembayaran

### Upload (di app)

User upload file di form peminjaman (`/peminjaman/create`):
- File disimpan ke `storage/app/public/bukti_pembayaran/{filename}`
- Path disimpan di database column `peminjaman.bukti_pembayaran`

### Akses (di verifikasi page)

Admin melihat bukti di halaman verifikasi (`/pembayaran/verifikasi`):
- Klik tombol "Lihat" → buka URL `/pembayaran/bukti/{filename}`
- Route ini dilayani oleh `PembayaranController@showBukti`
- Controller membaca file dari storage dan return dengan MIME type yang benar

### Akses Direct (jika symlink ada)

Jika symlink sudah di-setup:
- File juga bisa diakses via `/storage/bukti_pembayaran/{filename}`
- Ini lebih cepat karena Nginx/Apache serve langsung, tidak via PHP

---

## Troubleshooting

### Error: "File not found" saat klik "Lihat Bukti"

**Kemungkinan 1**: File tidak ada di storage
- SSH ke Railway instance
- Cek: `ls -la storage/app/public/bukti_pembayaran/`
- Jika kosong: user belum ada yang upload, atau upload gagal

**Kemungkinan 2**: Path di database salah
- Query database: `SELECT id, bukti_pembayaran FROM peminjaman WHERE bukti_pembayaran IS NOT NULL LIMIT 5;`
- Path harus berupa `bukti_pembayaran/filename.jpg` (tidak `public/bukti_pembayaran/filename.jpg`)

**Kemungkinan 3**: Controller route tidak terdaftar
- Cek routes: `php artisan route:list | grep pembayaran`
- Harusnya ada route: `GET /pembayaran/bukti/{filename}`

### Bagaimana agar file persist di Railway?

Railway filesystem ephemeral = file hilang setelah reboot/deployment.

**Solusi terbaik**: Gunakan **S3 atau object storage** (AWS S3, DigitalOcean Spaces, Cloudflare R2):

1. Setup bucket di AWS S3 atau provider lain
2. Set env vars di Railway:
   ```
   FILESYSTEM_DRIVER=s3
   AWS_ACCESS_KEY_ID=<your-key>
   AWS_SECRET_ACCESS_KEY=<your-secret>
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=<your-bucket>
   ```
3. Ubah controller upload untuk store ke S3:
   ```php
   $file->storeAs('bukti_pembayaran', $filename, 's3');
   ```

Atau gunakan **Railway PostgreSQL Volume** (jika Railway supports) untuk store BLOB di database.

---

## Checklist Deployment ke Railway

- [ ] Push code ke GitHub
- [ ] Railway auto-build dari repo
- [ ] Set env vars di Railway dashboard
- [ ] Trigger manual deployment atau restart
- [ ] Railway runs `php artisan migrate --force`
- [ ] Railway runs `php artisan storage:setup` (jika Procfile/railway.toml configured)
- [ ] Test upload bukti pembayaran di `/peminjaman/create`
- [ ] Test lihat bukti di `/pembayaran/verifikasi` → klik "Lihat"
- [ ] Verify file muncul via `/pembayaran/bukti/{filename}` atau `/storage/bukti_pembayaran/{filename}`

---

## File yang Diubah

- `app/Http/Controllers/PembayaranController.php`: Upload logic consistent path
- `app/Console/Commands/SetupStorage.php`: New command untuk setup
- `routes/web.php`: Route `/pembayaran/bukti/{filename}`
- `app/Models/Peminjaman.php`: Accessor `bukti_pembayaran_src` return route URL
- `resources/views/pembayaran/verifikasi.blade.php`: Use accessor untuk link

---

## Referensi

- [Laravel Storage](https://laravel.com/docs/10.x/filesystem)
- [Laravel on Railway](https://docs.railway.app/guides/laravel)
- [AWS S3 with Laravel](https://laravel.com/docs/10.x/filesystem#s3-driver-configuration)

