# Fitur Reset Password

## Deskripsi
Sistem reset password dengan 2 pilihan:
1. **Via Email** - Verifikasi dengan kode 6 digit yang dikirim ke email
2. **Via WhatsApp** - Chat langsung dengan admin untuk bantuan reset password

## Alur Reset Password (Via Email)

### 1. Request Reset Password
- **URL:** `/password/reset`
- User memilih metode reset (Email atau WhatsApp)
- Untuk email: masukkan alamat email terdaftar
- Sistem generate kode 6 digit random
- Kode disimpan di database `password_reset_tokens` (berlaku 15 menit)
- Email dikirim ke user berisi kode verifikasi

### 2. Verifikasi Kode
- **URL:** `/password/verify`
- User memasukkan 6 digit kode verifikasi
- Auto-focus dan auto-tab untuk kemudahan input
- Support paste kode langsung
- Timer countdown 15 menit
- Validasi: kode harus sesuai, belum expired, dan belum digunakan

### 3. Set Password Baru
- **URL:** `/password/reset/new`
- User memasukkan password baru (min 8 karakter)
- Password strength indicator real-time
- Validasi requirements:
  - Minimal 8 karakter
  - Minimal 1 huruf besar
  - Minimal 1 huruf kecil
  - Minimal 1 angka
- Konfirmasi password dengan indikator match
- Toggle show/hide password

### 4. Selesai
- Password berhasil direset
- Token ditandai sebagai `used`
- Session dibersihkan
- Redirect ke halaman login

## Database Structure

### Tabel: `password_reset_tokens`
```sql
CREATE TABLE password_reset_tokens (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(6) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX (email)
);
```

## Routes

```php
// Password Reset Routes
Route::get('/password/reset', [AuthController::class, 'resetRequestForm'])->name('password.request');
Route::post('/password/reset/send-code', [AuthController::class, 'sendResetCode'])->name('password.send-code');
Route::get('/password/verify', [AuthController::class, 'verifyCodeForm'])->name('password.verify');
Route::post('/password/verify', [AuthController::class, 'verifyCode'])->name('password.verify.post');
Route::get('/password/reset/new', [AuthController::class, 'newPasswordForm'])->name('password.reset.new');
Route::post('/password/reset/update', [AuthController::class, 'updatePassword'])->name('password.update');
```

## Fitur-Fitur UI

### Halaman Request Reset (`password-reset-request.blade.php`)
- Dual option: Email dan WhatsApp
- WhatsApp link dengan pre-filled message
- Email validation (must exist in database)
- Responsive design dengan gradient background
- Icon FontAwesome untuk visual clarity

### Halaman Verifikasi Kode (`password-verify-code.blade.php`)
- 6 input boxes untuk 6 digit kode
- Auto-focus otomatis ke input berikutnya
- Auto-backspace ke input sebelumnya
- Support paste kode langsung (auto-distribute)
- Real-time countdown timer (15:00)
- Resend code option
- Email user ditampilkan untuk referensi

### Halaman Password Baru (`password-reset-new.blade.php`)
- Password strength meter (Lemah/Cukup/Baik/Kuat)
- Real-time validation indicators
- Toggle show/hide password (untuk password & konfirmasi)
- Match indicator untuk konfirmasi password
- Checklist persyaratan password
- Prevent submission jika requirements tidak terpenuhi

## Konfigurasi Email

Pastikan sudah set di `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Untuk Gmail:
1. Aktifkan 2-Factor Authentication
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Gunakan App Password di `MAIL_PASSWORD`

## Testing

### Jalankan Migration
```bash
php artisan migrate
```

### Test Reset Password Flow
1. Akses `/password/reset`
2. Masukkan email yang terdaftar
3. Cek inbox email untuk kode verifikasi
4. Masukkan kode 6 digit
5. Set password baru
6. Login dengan password baru

### Test WhatsApp Option
1. Klik "Chat Admin WhatsApp"
2. Browser membuka WhatsApp dengan pesan template
3. User chat dengan admin untuk bantuan manual

## Security Features

1. **Token Expiration:** Kode verifikasi berlaku 15 menit
2. **One-Time Use:** Token otomatis ditandai `used` setelah berhasil
3. **Session Management:** Email dan token_id disimpan di session untuk keamanan
4. **Email Validation:** Hanya email terdaftar yang bisa request reset
5. **Password Hashing:** Password baru di-hash dengan bcrypt
6. **CSRF Protection:** Semua form dilindungi CSRF token

## Error Handling

- Email tidak terdaftar → Error message
- Kode verifikasi salah → Error message
- Kode expired → Error message
- Session expired → Redirect ke awal dengan pesan error
- Email sending gagal → Fallback message dengan saran hubungi admin

## Customization

### Ubah Nomor WhatsApp Admin
Edit di `password-reset-request.blade.php`:
```html
<a href="https://wa.me/6281234567890?text=...">
```
Ganti `6281234567890` dengan nomor admin Anda (format internasional).

### Ubah Durasi Token
Edit di `AuthController.php` method `sendResetCode()`:
```php
'expires_at' => now()->addMinutes(15), // Ubah 15 ke durasi lain
```

### Ubah Template Email
Edit di `AuthController.php` method `sendResetCode()`:
```php
\Mail::raw("Kode verifikasi reset password Anda adalah: {$code}\n\nKode ini berlaku selama 15 menit.", ...);
```

## Troubleshooting

### Email Tidak Terkirim
1. Cek konfigurasi `.env` sudah benar
2. Test koneksi SMTP:
   ```bash
   php artisan tinker
   Mail::raw('Test', function($msg) { $msg->to('test@example.com'); });
   ```
3. Cek log: `storage/logs/laravel.log`
4. Pastikan firewall tidak block port 587

### Kode Verifikasi Invalid
1. Cek timestamp server vs database (timezone)
2. Clear cache: `php artisan cache:clear`
3. Cek data di tabel `password_reset_tokens`

### Session Expired Terus
1. Pastikan session driver correct di `.env`
2. Clear session: `php artisan session:flush`
3. Cek permission folder `storage/framework/sessions`

## Production Checklist

- [ ] Konfigurasi email production di `.env`
- [ ] Test kirim email production
- [ ] Set nomor WhatsApp admin yang benar
- [ ] Enable SSL/TLS untuk email (port 587 atau 465)
- [ ] Set session driver yang persistent (database/redis)
- [ ] Monitor queue jika menggunakan queued mail
- [ ] Backup tabel `password_reset_tokens` secara berkala
- [ ] Set up email monitoring/logging
- [ ] Test complete flow di production environment

## Maintenance

### Cleanup Old Tokens
Jalankan secara berkala (bisa via cron):
```php
DB::table('password_reset_tokens')
    ->where('expires_at', '<', now())
    ->orWhere('used', true)
    ->where('created_at', '<', now()->subDays(7))
    ->delete();
```

Atau buat command artisan:
```bash
php artisan make:command CleanupPasswordResetTokens
```
