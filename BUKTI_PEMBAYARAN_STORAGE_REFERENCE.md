# Bukti Pembayaran File Storage - Quick Reference

## Current Status

### ✅ Local Storage (Default)
- **Location**: `storage/app/public/bukti_pembayaran/`
- **Persistence**: ❌ Files lost on Railway redeploy (ephemeral filesystem)
- **Cost**: Free (included in Rails PHP plan)
- **Access**: `/pembayaran/bukti/{filename}` route
- **Best for**: Development, testing

### ✅ S3 Storage (Recommended for Production)
- **Location**: AWS S3 bucket (`ukk-sewa-ruang`)
- **Persistence**: ✅ Files persist forever (unless manually deleted)
- **Cost**: ~$0-5/month (free tier available)
- **Access**: `/pembayaran/bukti/{filename}` route (auto-routes to S3)
- **Best for**: Production deployment

---

## Quick Setup

### For Local Storage (Development)
```bash
# Already configured by default
# Just create directory:
mkdir -p storage/app/public/bukti_pembayaran

# Procfile akan auto-setup di Railway
```

### For S3 Storage (Production)
```bash
# 1. Create AWS S3 bucket + IAM user (see S3_SETUP_GUIDE.md)
# 2. Set Railway variables:
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=<key>
AWS_SECRET_ACCESS_KEY=<secret>
AWS_BUCKET=ukk-sewa-ruang
AWS_DEFAULT_REGION=ap-southeast-1

# 3. Redeploy Railway app
# Done! Files will auto-upload to S3
```

---

## How It Works (Behind the Scenes)

### Upload Flow
```
User uploads file via web form
    ↓
PembayaranController::uploadBukti()
    ↓
Detect FILESYSTEM_DISK config
    ↓
If 'local' → store to storage/app/public/bukti_pembayaran/
If 's3'    → store to S3 bucket/bukti_pembayaran/
    ↓
Save relative path to database: "bukti_pembayaran/timestamp_filename.jpg"
```

### Access Flow
```
Admin clicks "Lihat" (View) on Verification page
    ↓
Peminjaman model accessor generates URL: /pembayaran/bukti/filename.jpg
    ↓
Browser requests: /pembayaran/bukti/filename.jpg
    ↓
PembayaranController::showBukti()
    ↓
Detect FILESYSTEM_DISK config
    ↓
If 'local' → serve from storage/app/public/
If 's3'    → stream from S3 bucket
    ↓
Browser displays image
```

---

## File Locations

### Database Column
```
peminjaman.bukti_pembayaran = "bukti_pembayaran/1704067425_invoice.jpg"
```

### Local Disk
```
storage/app/public/bukti_pembayaran/1704067425_invoice.jpg
```

### S3 Bucket Path
```
s3://ukk-sewa-ruang/bukti_pembayaran/1704067425_invoice.jpg
```

### Accessible Via Route
```
GET /pembayaran/bukti/1704067425_invoice.jpg
```

---

## Configuration Files

### `config/filesystems.php`
```php
'default' => env('FILESYSTEM_DISK', 'local'),

'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
    ],
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
    ],
]
```

### `.env` Variables
```
# Default (development)
FILESYSTEM_DISK=local

# Or for production S3
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=ukk-sewa-ruang
```

---

## Code Implementations

### Upload
**File**: `app/Http/Controllers/PembayaranController.php`
```php
$disk = config('filesystems.default') === 's3' ? 's3' : 'public';
$relativePath = $file->storeAs('bukti_pembayaran', $filename, $disk);
```

### Serve
**File**: `app/Http/Controllers/PembayaranController.php`
```php
$disk = config('filesystems.default') === 's3' ? 's3' : 'public';
if (Storage::disk($disk)->exists($candidate)) {
    $stream = Storage::disk($disk)->get($candidate);
    return response($stream, 200, ['Content-Type' => $mime]);
}
```

### Model Accessor
**File**: `app/Models/Peminjaman.php`
```php
$disk = config('filesystems.default') === 's3' ? 's3' : 'public';
return route('pembayaran.bukti', ['filename' => basename($candidate)]);
```

---

## Testing Uploads

### Curl Command
```bash
# Create test peminjaman first
curl -X POST http://localhost:8000/peminjaman \
  -F "ruang_id=1" \
  -F "tanggal=2025-01-15" \
  -F "jam_mulai=10:00" \
  -F "jam_selesai=11:00" \
  -F "keperluan=meeting" \
  -F "bukti_pembayaran=@/path/to/image.jpg"

# Then access it
curl http://localhost:8000/pembayaran/bukti/1704067425_invoice.jpg
```

### Local Browser
1. Open `http://localhost:8000/peminjaman/create`
2. Fill form and upload image
3. Check `storage/app/public/bukti_pembayaran/` for file

### Railway Browser
1. Open `https://sewa-ruang.up.railway.app/peminjaman/create`
2. Fill form and upload image
3. Wait 30 seconds for S3 upload
4. Click "Lihat" to view image

---

## Troubleshooting

### Files returning 404
```
GET /pembayaran/bukti/1704067425_invoice.jpg → 404
```
**Causes**:
- Directory doesn't exist (local): Create `storage/app/public/bukti_pembayaran/`
- S3 credentials wrong: Check Railway variables
- File path mismatch: Check database vs filesystem

**Fix**:
- Run `php artisan storage:setup` to create directories
- Verify S3 credentials in Railway
- Check database: `SELECT bukti_pembayaran FROM peminjaman LIMIT 1;`

### Slow upload on Railway
```
Uploading to S3...  (taking 10+ seconds)
```
**Causes**:
- Network latency to AWS S3
- Large file size
- Slow Railway instance

**Fix**:
- Compress images before upload (recommended max 2MB)
- Use CDN like CloudFront if many requests

### Storage link not working
```
Symlink /public/storage → /storage/app/public not created
```
**Expected**: App should still work via `/pembayaran/bukti/` route
**Fallback**: Controller method serves files directly

---

## Migration from Local to S3

### Without Data Loss

```bash
# 1. Export local files
find storage/app/public/bukti_pembayaran -type f

# 2. Create S3 bucket and setup credentials
# (See S3_SETUP_GUIDE.md)

# 3. Update Railway variables
FILESYSTEM_DISK=s3

# 4. Upload existing files to S3 (one-time)
php artisan app:migrate-bukti-to-s3

# 5. Test access
# Files will auto-serve from S3
```

---

## Cost Estimates

### Local Storage (Railway)
- **Monthly Cost**: Included in server fee (~$7)
- **Storage Limit**: 1GB (shared with entire app)
- **Persistence**: ❌ Lost on redeploy

### S3 Storage (AWS)
- **Monthly Cost**: $0 (free tier 5GB) → ~$0.12/GB after
- **Storage Limit**: 5GB free, then pay-per-use
- **Persistence**: ✅ Permanent

### Example: 100 Payments/Month
- **Local**: ~10MB → included, free
- **S3**: ~10MB/month → **$0.24/month** (free tier)

---

## Production Deployment Checklist

- [ ] S3 bucket created (`ukk-sewa-ruang`)
- [ ] IAM user credentials generated
- [ ] Railway variables set (`FILESYSTEM_DISK=s3`)
- [ ] AWS credentials added to Railway env
- [ ] Code deployed to Railway
- [ ] Test upload via web UI
- [ ] Verify file in S3 bucket
- [ ] Verify access via `/pembayaran/bukti/{filename}`
- [ ] Test on mobile (if applicable)
- [ ] Monitor Railway logs for errors

---

## Support / Documentation

- **Local Storage**: See `RAILWAY_STORAGE_SETUP.md`
- **S3 Setup**: See `S3_SETUP_GUIDE.md`
- **Code**: `app/Http/Controllers/PembayaranController.php`
- **Model**: `app/Models/Peminjaman.php`
- **Route**: `routes/web.php` → `pembayaran.bukti`
