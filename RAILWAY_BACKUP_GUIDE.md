# Railway Backup Guide

## Masalah dengan Railway Ephemeral Storage

Railway menggunakan ephemeral filesystem, artinya file yang disimpan di storage akan hilang setiap kali aplikasi di-redeploy atau restart. Ini membuat sistem backup tradisional (simpan file di server) tidak cocok untuk Railway.

## Solusi yang Diterapkan

### 1. Auto-Download saat Backup Manual
- Ketika admin membuat backup manual di production (Railway), file akan langsung didownload ke komputer admin
- File tetap tersimpan sementara di database record untuk tracking
- Tombol "Buat & Download Backup Sekarang" akan langsung memicu download

### 2. Auto-Regenerate untuk Download
- Jika admin mencoba download backup lama yang filenya sudah hilang, sistem akan:
  1. Deteksi file tidak ada
  2. Generate backup baru otomatis
  3. Download backup baru tersebut
- Ini memastikan admin selalu bisa mendapatkan backup meskipun file lama hilang

### 3. Cara Menggunakan di Railway

#### Backup Manual:
1. Login sebagai admin
2. Masuk menu "Backup DB"
3. Klik "Buat & Download Backup Sekarang"
4. File .sql akan langsung terdownload ke komputer Anda
5. **PENTING**: Simpan file ini di tempat aman (Google Drive, Dropbox, dll)

#### Restore Database:
1. Masuk menu "Backup DB"
2. Klik "Restore dari File"
3. Upload file .sql yang sudah Anda simpan
4. Sistem akan restore database

### 4. Backup Otomatis Terjadwal

⚠️ **Peringatan**: Backup otomatis di Railway tidak akan tersimpan permanen!

Untuk backup otomatis yang reliable di Railway, Anda memiliki beberapa opsi:

#### Opsi A: Gunakan Railway PostgreSQL Backups (Recommended jika pakai PostgreSQL)
Railway menyediakan automatic backup untuk PostgreSQL database.

#### Opsi B: Setup Cron Job + Cloud Storage
1. Install Google Drive API atau AWS S3 package
2. Modifikasi `DatabaseBackupService` untuk upload ke cloud storage
3. Setup cron job untuk backup otomatis

#### Opsi C: Gunakan External Backup Service
- Backup database langsung dari Railway MySQL ke service seperti:
  - BackupBuddy
  - SimpleBackups
  - DatabaseBackup.io

### 5. Best Practices

1. **Download backup setelah dibuat**: Selalu download dan simpan backup secara manual
2. **Simpan di multiple locations**: Google Drive, Dropbox, dan local computer
3. **Test restore**: Sesekali test restore di local environment untuk memastikan backup valid
4. **Regular backups**: Buat backup manual sebelum:
   - Deploy update besar
   - Modifikasi database struktur
   - Delete data dalam jumlah besar

### 6. Monitoring

- Check log Railway untuk error backup: `railway logs`
- Monitor ukuran database di Railway dashboard
- Keep track of backup frequency

## Troubleshooting

### "File backup tidak ditemukan"
**Penyebab**: File hilang karena redeploy/restart
**Solusi**: Klik download lagi, sistem akan generate backup baru otomatis

### Backup gagal dibuat
**Cek**:
1. Database connection di Railway
2. Memory limit (backup database besar butuh memory)
3. Railway logs: `railway logs --follow`

### File SQL corrupt saat restore
**Solusi**:
1. Re-download backup
2. Check file encoding (harus UTF-8)
3. Buka file di text editor, cek apakah struktur SQL valid

## Future Improvements

Untuk implementasi yang lebih robust, pertimbangkan:

1. **Google Drive Integration**:
   ```bash
   composer require google/apiclient
   ```
   - Auto-upload backup ke Google Drive
   - User bisa link ke Google account mereka
   - Backup otomatis tersimpan permanen

2. **AWS S3 Integration**:
   ```bash
   composer require league/flysystem-aws-s3-v3
   ```
   - Upload ke S3 bucket
   - Lifecycle policy untuk auto-delete old backups

3. **Email Backup**:
   - Kirim file .sql via email ke admin
   - Untuk database kecil-menengah

## Kesimpulan

Sistem backup saat ini di Railway:
- ✅ Berfungsi untuk backup manual dengan immediate download
- ✅ Auto-regenerate jika file hilang
- ⚠️ Memerlukan manual save oleh admin
- ❌ Backup otomatis tidak persistent

Untuk production yang serius, sangat disarankan untuk implement cloud storage integration.
