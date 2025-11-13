# 📱 Setup Laravel API untuk Flutter

Dokumentasi lengkap untuk menghubungkan Flutter app ke Laravel WOWOK sebagai API backend.

---

## ✅ Langkah 1: Install Laravel Sanctum

Sanctum adalah library auth token yang ringan untuk mobile app.

```bash
cd c:\laragon\www\WOWOK
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

**Output yang diharapkan:**
```
Migration table created successfully.
Migrations: ... created_personal_access_tokens_table
```

---

## ✅ Langkah 2: Verifikasi Konfigurasi

### Config CORS (sudah fixed)
File: `config/cors.php` - sudah diset dengan:
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['*'],  // Development only
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

### Auth Config
Pastikan di `config/auth.php`, guards ada `sanctum`:
```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'sanctum' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
],
```

---

## ✅ Langkah 3: Model User dengan Sanctum

File: `app/Models/User.php` sudah ada, pastikan include trait Sanctum:

```php
<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;  // ← tambahkan HasApiTokens

    protected $fillable = ['name', 'email', 'password', 'role'];
    protected $hidden = ['password', 'remember_token'];

    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class, 'user_id');
    }
}
```

---

## ✅ Langkah 4: AuthController (sudah diupdate)

File: `app/Http/Controllers/AuthController.php` sudah diupdate dengan method API:

- `apiRegister(Request $request)` → POST `/api/register`
- `apiLogin(Request $request)` → POST `/api/login`
- `apiProfile(Request $request)` → GET `/api/user` (protected)
- `apiLogout(Request $request)` → POST `/api/logout` (protected)

---

## ✅ Langkah 5: Routes API (sudah diupdate)

File: `routes/api.php` sudah diupdate dengan:

```php
// Public routes
POST   /api/register       → register user
POST   /api/login          → login & get token

// Protected routes (memerlukan Authorization header)
GET    /api/user           → get profile
POST   /api/logout         → logout & revoke token
```

---

## 🚀 Langkah 6: Jalankan Server Laravel

```bash
cd c:\laragon\www\WOWOK
php artisan serve --host=0.0.0.0 --port=8000
```

Output:
```
Laravel development server started: http://127.0.0.1:8000
```

Server akan berjalan di:
- **Local**: `http://127.0.0.1:8000`
- **Network**: `http://192.168.x.x:8000` (ganti X dengan IP Anda)
- **Android Emulator**: `http://10.0.2.2:8000`

---

## 🧪 Testing API dengan cURL/Postman

### 1️⃣ Register User

```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Response (201 Created):**
```json
{
  "message": "Registrasi berhasil",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "pengunjung",
      "created_at": "2025-11-13T10:30:00.000000Z",
      "updated_at": "2025-11-13T10:30:00.000000Z"
    },
    "token": "5|abcdef1234567890..."
  }
}
```

**Catat tokennya!** Anda akan pakai di request berikutnya.

---

### 2️⃣ Login User

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

**Response (200 OK):**
```json
{
  "message": "Login berhasil",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "pengunjung"
    },
    "token": "5|abcdef1234567890..."
  }
}
```

---

### 3️⃣ Get Profile (dengan Token)

```bash
curl -X GET http://127.0.0.1:8000/api/user \
  -H "Authorization: Bearer 5|abcdef1234567890..."
```

**Response (200 OK):**
```json
{
  "message": "Profil user",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "pengunjung",
    "created_at": "2025-11-13T10:30:00.000000Z",
    "updated_at": "2025-11-13T10:30:00.000000Z"
  }
}
```

---

### 4️⃣ Logout (dengan Token)

```bash
curl -X POST http://127.0.0.1:8000/api/logout \
  -H "Authorization: Bearer 5|abcdef1234567890..."
```

**Response (200 OK):**
```json
{
  "message": "Logout berhasil"
}
```

---

## 📱 Konfigurasi Flutter

Di file Flutter `lib/services/api_services.dart`, atur baseUrl sesuai kebutuhan:

### Untuk Android Emulator:
```dart
static const String baseUrl = "http://10.0.2.2:8000/api";
```

### Untuk iOS Simulator:
```dart
static const String baseUrl = "http://localhost:8000/api";
```

### Untuk Device Fisik (ubah IP sesuai mesin Anda):
```dart
static const String baseUrl = "http://192.168.1.100:8000/api";
```

Pastikan firewall Anda mengizinkan port 8000.

---

## 🔧 Troubleshooting

### ❌ Error: "TokenMismatchException" atau CSRF Token

**Solusi:** Ini adalah error session/CSRF untuk web form, bukan API. Abaikan untuk API endpoint.

### ❌ Error: "Unauthenticated" (401)

**Penyebab:** 
- Token tidak dikirim atau salah format
- Token sudah expired atau invalid

**Solusi:**
- Pastikan header: `Authorization: Bearer <TOKEN>`
- Jangan lupa `Bearer ` sebelum token
- Coba login lagi untuk dapatkan token baru

### ❌ Error: CORS issue

**Penyebab:** Origin request tidak diizinkan

**Solusi (untuk development):**
```php
// config/cors.php
'allowed_origins' => ['*'],
```

Untuk production, spesifik origin Anda.

### ❌ Error: "The password should be confirmed"

**Penyebab:** Saat register, `password_confirmation` tidak match dengan `password`

**Solusi:** Pastikan kirim keduanya dengan nilai sama:
```json
{
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

### ❌ Error: "Connection refused" atau "Unable to connect"

**Penyebab:** Server Laravel tidak running atau port salah

**Solusi:**
1. Pastikan Laravel server berjalan: `php artisan serve --host=0.0.0.0 --port=8000`
2. Test dari command line: `curl http://127.0.0.1:8000/api/login`
3. Cek baseUrl di Flutter (Android emulator pakai `10.0.2.2`)

---

## 📋 Checklist Implementasi

- [ ] `composer require laravel/sanctum` installed
- [ ] `php artisan migrate` executed
- [ ] `AuthController.php` updated dengan API methods
- [ ] `routes/api.php` updated dengan auth routes
- [ ] `config/cors.php` configured (sudah done)
- [ ] `User.php` model include `HasApiTokens`
- [ ] Server Laravel running: `php artisan serve`
- [ ] Test register/login dengan cURL berhasil
- [ ] Flutter app siap dengan baseUrl
- [ ] Flutter app bisa register/login/logout

---

## 📚 Endpoint Summary

| Method | Route | Auth | Deskripsi |
|--------|-------|------|-----------|
| POST | `/api/register` | ❌ | Register user baru |
| POST | `/api/login` | ❌ | Login & dapatkan token |
| GET | `/api/user` | ✅ | Get profile user |
| POST | `/api/logout` | ✅ | Logout & revoke token |

✅ = Memerlukan Authorization header dengan token Sanctum

---

## 🎯 Next Steps

1. **Run Sanctum setup** (jika belum):
   ```bash
   composer require laravel/sanctum
   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
   php artisan migrate
   ```

2. **Start server**: `php artisan serve --host=0.0.0.0 --port=8000`

3. **Test API** dengan cURL commands di atas

4. **Update Flutter baseUrl** sesuai environment

5. **Test dari Flutter app**: Register → Login → Get Profile → Logout

---

Jika ada error atau pertanyaan, check bagian **Troubleshooting** atau tanya support! 🚀
