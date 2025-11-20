# Backup Database ke Supabase

## Fitur
- Backup database web otomatis di-upload ke Supabase Storage bucket `UKK`.
- File backup SQL bisa langsung di-download dari halaman backup web.
- Daftar file backup di Supabase ditampilkan di halaman backup, bisa diunduh kapan saja.

## Cara Penggunaan
1. Buka halaman backup database di aplikasi web.
2. Klik tombol **Download Backup Database** untuk membuat backup baru.
   - File SQL akan otomatis di-upload ke Supabase dan didownload ke komputer Anda.
3. Di bagian "Backup di Supabase", akan muncul daftar file backup yang tersimpan di Supabase.
   - Klik link **Download** untuk mengunduh file backup dari Supabase Storage.

## Konfigurasi
- Pastikan file `.env` sudah berisi:
  ```
  SUPABASE_URL=https://kqmmpqjaqnhsfmrcsuwm.supabase.co
  SUPABASE_SERVICE_KEY=sbp_36d4f94b9ed0fd2c474589e89737f845dee28b42
  SUPABASE_BUCKET=UKK
  ```
- Konfigurasi otomatis digunakan oleh aplikasi.

## Restore
- Untuk restore database, gunakan menu **Restore dari File** di halaman backup.
- Pilih file SQL hasil backup (bisa dari Supabase atau download manual).

## Catatan
- Backup SQL juga bisa diakses langsung dari dashboard Supabase Storage.
- Pastikan service key dan bucket sudah benar agar upload berjalan lancar.
