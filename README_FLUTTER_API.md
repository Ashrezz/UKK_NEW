# 🚀 Flutter + Laravel API Integration

## ✅ Setup Selesai!

Larangan WOWOK sudah dikonfigurasi sebagai API backend untuk Flutter app.

---

## 📊 Apa yang sudah dilakukan:

### ✅ Laravel Backend Setup
- **Sanctum Authentication** - Token-based auth untuk mobile
- **4 API Endpoints** - Register, Login, Get Profile, Logout
- **CORS Enabled** - Ready untuk request dari Flutter
- **Server Running** - `http://127.0.0.1:8000`

### ✅ Files Modified
| File | Perubahan |
|------|-----------|
| `app/Http/Controllers/AuthController.php` | + API methods (apiRegister, apiLogin, apiProfile, apiLogout) |
| `app/Models/User.php` | + HasApiTokens trait |
| `routes/api.php` | + Auth routes (/register, /login, /user, /logout) |

### ✅ Documentation Created
| File | Konten |
|------|--------|
| `FLUTTER_API_SETUP.md` | Setup guide lengkap (Sanctum, config, testing) |
| `FLUTTER_INTEGRATION_GUIDE.md` | Flutter code examples (ApiService, UI widgets) |
| `FLUTTER_API_SETUP_COMPLETE.md` | Quick reference & checklist |
| `api_services.dart.example` | Ready-to-copy Flutter service class |

### ✅ Tested & Working
```
✓ POST /api/register → 201 Created
✓ POST /api/login → 200 OK (with token)
✓ GET /api/user → 200 OK (with valid token)
✓ POST /api/logout → 200 OK (revoke token)
```

---

## 🚀 Quick Start

### 1️⃣ Start Laravel Server
```bash
cd c:\laragon\www\WOWOK
php artisan serve --host=0.0.0.0 --port=8000
```

### 2️⃣ Setup Flutter App
```bash
# A. Add dependencies
flutter pub get

# B. Copy api_services.dart
# Copy dari: c:\laragon\www\WOWOK\api_services.dart.example
# Ke: <flutter-project>/lib/services/api_services.dart

# C. Update baseUrl sesuai platform:
# - Android Emulator: http://10.0.2.2:8000/api
# - iOS Simulator: http://localhost:8000/api
# - Device Fisik: http://192.168.X.X:8000/api
```

### 3️⃣ Use in Flutter Widget
```dart
// Register
await ApiService.register(
  name: 'John',
  email: 'john@example.com',
  password: 'password123',
  passwordConfirmation: 'password123'
);

// Login
await ApiService.login(
  email: 'john@example.com',
  password: 'password123'
);

// Get Profile
final profile = await ApiService.getProfile();

// Logout
await ApiService.logout();
```

---

## 📚 Documentation Files

Baca file-file ini sesuai kebutuhan:

1. **FLUTTER_API_SETUP.md** ← Mulai dari sini jika ingin detail setup
2. **FLUTTER_INTEGRATION_GUIDE.md** ← Lihat contoh Flutter code
3. **FLUTTER_API_SETUP_COMPLETE.md** ← Quick reference
4. **api_services.dart.example** ← Copy ke Flutter project

---

## 🧪 Test API

### Register (PowerShell)
```powershell
$body = @{
  name="Test"
  email="test@example.com"
  password="pass123"
  password_confirmation="pass123"
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/register" `
  -Method POST `
  -Headers @{"Content-Type"="application/json"} `
  -Body $body
```

### Login (PowerShell)
```powershell
$body = @{
  email="test@example.com"
  password="pass123"
} | ConvertTo-Json

$response = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/login" `
  -Method POST `
  -Headers @{"Content-Type"="application/json"} `
  -Body $body

$response.Content  # Lihat token
```

### Get Profile (PowerShell)
```powershell
$token = "PASTE_TOKEN_HERE"

Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/user" `
  -Method GET `
  -Headers @{"Authorization"="Bearer $token"}
```

---

## 🔗 API Endpoints

### Public Endpoints
| Method | Endpoint | Body |
|--------|----------|------|
| POST | `/api/register` | `{name, email, password, password_confirmation}` |
| POST | `/api/login` | `{email, password}` |

### Protected Endpoints (Require Token)
| Method | Endpoint | Response |
|--------|----------|----------|
| GET | `/api/user` | User profile |
| POST | `/api/logout` | Success message |

---

## 📱 Flutter Dependencies

Pastikan di `pubspec.yaml`:
```yaml
dependencies:
  http: ^1.1.0
  shared_preferences: ^2.0.0
```

---

## ⚠️ Important Notes

### Development
- CORS set to `*` (allow all) - safe untuk dev
- APP_DEBUG enabled
- Server running di `0.0.0.0:8000`

### Production
- Ubah CORS ke specific domain
- Set APP_DEBUG=false
- Use HTTPS
- Add rate limiting

---

## 🆘 Troubleshooting

### Server tidak jalan
```bash
# Check if port 8000 is in use
netstat -ano | findstr :8000

# Kill process (jika perlu)
taskkill /PID <PID> /F

# Run server
php artisan serve --host=0.0.0.0 --port=8000
```

### Connection refused dari Flutter
→ Pastikan baseUrl benar:
- Android: `http://10.0.2.2:8000/api`
- iOS: `http://localhost:8000/api`
- Device: `http://192.168.X.X:8000/api`

### 401 Unauthorized
→ Token tidak dikirim atau expired
→ Cek header: `Authorization: Bearer <TOKEN>`

### 422 Validation Error
→ Ada field yang invalid
→ Cek response error messages

---

## 📋 Checklist

- [ ] Laravel server running di 127.0.0.1:8000
- [ ] Can register user via cURL/Postman
- [ ] Can login and get token
- [ ] Can get profile with token
- [ ] Flutter project created
- [ ] http & shared_preferences added to pubspec.yaml
- [ ] api_services.dart copied to Flutter project
- [ ] baseUrl updated untuk environment
- [ ] Flutter app can register
- [ ] Flutter app can login
- [ ] Token saved to SharedPreferences
- [ ] Can get profile dari Flutter
- [ ] Can logout dari Flutter

---

## 🎯 Next Steps

1. **Integrate ke Flutter UI** - Buat login/register screens
2. **Add more endpoints** - CRUD untuk peminjaman, ruang, dll
3. **Add validation** - Input validation, error handling
4. **Add loading states** - UI feedback saat loading
5. **Test thoroughly** - Unit tests, integration tests

---

## 📝 Notes

- Server harus selalu running saat test Flutter
- Token valid selama session
- Token disimpan di SharedPreferences
- Logout menghapus token dari storage dan server

---

## 🎉 Status

**✅ READY TO INTEGRATE!**

Laravel API sudah siap, dokumentasi lengkap tersedia, tinggal integrate ke Flutter app.

Selamat mengembangkan! 🚀

---

**Kontribusi & Pertanyaan:**
Untuk issue atau pertanyaan, check:
1. Laravel logs: `storage/logs/laravel.log`
2. Flutter console output
3. Documentation files

Last Updated: November 13, 2025
