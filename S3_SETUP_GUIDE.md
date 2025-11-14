# AWS S3 Setup Guide untuk Bukti Pembayaran Storage

## Overview

Aplikasi mendukung penyimpanan bukti pembayaran di AWS S3. Ini memberikan manfaat:
- **Persistent Storage**: Files tidak hilang saat Railway app di-redeploy atau restart
- **Scalability**: Tidak bergantung pada kapasitas server lokal
- **Reliability**: AWS S3 memiliki uptime 99.99%
- **Cost-effective**: ~$0-5/month untuk usage kecil

> **Catatan**: Saat ini, aplikasi masih bisa berjalan dengan **local storage** (default) untuk development dan testing.

---

## Step 1: Create AWS S3 Bucket

### Prerequisites
- AWS account (free tier mencakup 5GB S3 storage gratis untuk 12 bulan pertama)

### Create Bucket
1. Login ke [AWS Console](https://console.aws.amazon.com/)
2. Pergi ke **S3 service**
3. Klik **Create Bucket**
4. **Bucket name**: `ukk-sewa-ruang` (nama harus unik globally)
5. **Region**: Pilih terdekat dengan users (e.g., `ap-southeast-1` untuk Asia)
6. **Block all public access**: Tetap checked (kita akan gunakan pre-signed URLs)
7. Klik **Create Bucket**

---

## Step 2: Create IAM User dengan S3 Permissions

### Create IAM User
1. Pergi ke **IAM** → **Users**
2. Klik **Create User**
3. **User name**: `ukk-app-s3-user`
4. Klik **Next**

### Attach Permissions
1. **Attach policies directly**
2. Cari policy `AmazonS3FullAccess` dan pilih
3. Klik **Next** → **Create User**

### Generate Access Keys
1. Klik user yang baru dibuat
2. Pergi ke **Security Credentials** tab
3. Klik **Create access key**
4. Pilih **Application running outside AWS**
5. Klik **Next**
6. Klik **Create access key**
7. **Copy dan simpan**:
   - `Access Key ID`
   - `Secret Access Key`

> ⚠️ **PENTING**: Secret Access Key hanya ditampilkan sekali! Simpan di tempat aman.

---

## Step 3: Configure Environment Variables di Railway

### Via Railway Dashboard
1. Login ke [Railway.app](https://railway.app/)
2. Buka project
3. Klik **Variables**
4. Tambahkan environment variables:

```
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=<your-access-key-id>
AWS_SECRET_ACCESS_KEY=<your-secret-access-key>
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=ukk-sewa-ruang
```

### Contoh AWS_ACCESS_KEY_ID
```
AKIAIOSFODNN7EXAMPLE
```

### Contoh AWS_SECRET_ACCESS_KEY
```
wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
```

### Cek Regional Endpoints
- `ap-southeast-1` (Singapore)
- `ap-southeast-2` (Sydney)
- `us-east-1` (N. Virginia, default)
- [Semua regions](https://docs.aws.amazon.com/general/latest/gr/s3.html)

---

## Step 4: Deploy ke Railway

1. Commit dan push code ke GitHub:
   ```bash
   git add -A
   git commit -m "Configure S3 storage"
   git push origin main
   ```

2. Railway akan auto-deploy
3. Verifikasi di Railway logs:
   ```
   FILESYSTEM_DISK=s3 -> Files akan upload ke S3
   ```

---

## Step 5: Test S3 Upload

### Via Web UI
1. Login ke aplikasi sebagai **user** (siswa/pegawai)
2. Pergi ke **Peminjaman Ruang** → **Create**
3. Upload bukti pembayaran
4. Submit

### Expected Behavior
- File akan di-upload ke S3 bucket
- Database menyimpan relative path: `bukti_pembayaran/1234567890_invoice.jpg`
- Controller route `/pembayaran/bukti/{filename}` akan serve dari S3

### Troubleshooting

**Error: "Access Denied" / 403**
- Cek credentials di Railway variables
- Verifikasi IAM user punya S3FullAccess
- Cek bucket name di AWS_BUCKET

**Error: "InvalidAccessKeyId"**
- Copy-paste credentials dengan hati-hati (jangan ada spaces)
- Generate access key baru jika perlu

**File tidak ditemukan setelah upload**
- Cek di AWS S3 console apakah file ada di bucket
- Verifikasi region di AWS_DEFAULT_REGION

---

## Step 6: Verify S3 Bucket

### Via AWS Console
1. Pergi ke **S3** → Bucket **ukk-sewa-ruang**
2. Klik folder **bukti_pembayaran**
3. Seharusnya ada files yang di-upload
4. Klik file → **Open** akan serve via controller route

### Via Railway Logs
```bash
# Upload successful
[2025-01-15 10:23:45] Bukti pembayaran berhasil diunggah

# Check logs
Storage disk: s3
File: bukti_pembayaran/1234567890_invoice.jpg
```

---

## Alternative: DigitalOcean Spaces (Cheaper)

Jika ingin lebih murah atau lebih sederhana:

### Setup
1. Create DigitalOcean account
2. Create Spaces bucket
3. Set di `.env`:
   ```
   FILESYSTEM_DISK=s3
   AWS_ENDPOINT=https://sgp1.digitaloceanspaces.com
   AWS_USE_PATH_STYLE_ENDPOINT=true
   AWS_ACCESS_KEY_ID=<spaces-key>
   AWS_SECRET_ACCESS_KEY=<spaces-secret>
   AWS_BUCKET=your-space-name
   ```

### Cost Comparison
- **AWS S3**: $0.023 per GB/month (after free tier)
- **DigitalOcean Spaces**: $5/month (unlimited storage up to 250GB)

---

## Fallback: Local Storage Only

Jika ingin tetap gunakan local storage (e.g., development):

1. Jangan set `FILESYSTEM_DISK=s3` di `.env`
2. Default: `FILESYSTEM_DISK=local` → files disimpan di `storage/app/public/`
3. Procfile akan auto-create directory dan symlink

### Limitation
- Files hilang saat Railway app di-redeploy (ephemeral filesystem)
- Cocok hanya untuk demo/development

---

## Production Checklist

- [ ] AWS S3 bucket created
- [ ] IAM user dengan credentials di-generate
- [ ] Railway variables `FILESYSTEM_DISK=s3` set
- [ ] AWS credentials di-add ke Railway
- [ ] Redeploy aplikasi
- [ ] Test upload file melalui web UI
- [ ] Verify file tersimpan di S3 bucket
- [ ] Verify file bisa di-access via `/pembayaran/bukti/{filename}`

---

## Security Best Practices

1. **Never commit credentials** ke Git
2. **Use IAM users** (jangan root account)
3. **Restrict IAM permissions** ke hanya S3FullAccess
4. **Rotate access keys** setiap 6 bulan
5. **Enable S3 bucket versioning** untuk backup:
   ```
   AWS Console → S3 → Bucket → Properties → Versioning
   ```
6. **Enable encryption** di-rest:
   ```
   AWS Console → S3 → Bucket → Properties → Default encryption
   Select: SSE-S3 (default AWS encryption)
   ```

---

## Documentation Links

- [AWS S3 Documentation](https://docs.aws.amazon.com/s3/)
- [Laravel Storage Documentation](https://laravel.com/docs/10.x/filesystem)
- [Railway Environment Variables](https://docs.railway.app/develop/variables)
- [DigitalOcean Spaces Documentation](https://docs.digitalocean.com/products/spaces/)
