# ✅ Laravel API Setup - COMPLETE! 

Dokumentasi ringkas hasil setup Laravel sebagai API untuk Flutter.

---

## 📊 Status Setup

✅ **Sanctum installed** - Laravel authentication token  
✅ **AuthController updated** - API methods added (register, login, profile, logout)  
✅ **routes/api.php updated** - Auth endpoints registered  
✅ **User model updated** - HasApiTokens trait added  
✅ **CORS configured** - Allow all origins (dev mode)  
✅ **Server tested** - Register & login working!

---

## 🌐 API Endpoints Summary

### Public Endpoints (No Auth Required)

| Method | Endpoint | Request Body | Response |
|--------|----------|--------------|----------|
| POST | `/api/register` | `{name, email, password, password_confirmation}` | `{message, data: {user, token}}` |
| POST | `/api/login` | `{email, password}` | `{message, data: {user, token}}` |

### Protected Endpoints (Auth Required)

| Method | Endpoint | Headers | Response |
|--------|----------|---------|----------|
| GET | `/api/user` | `Authorization: Bearer <token>` | `{message, data: user}` |
| POST | `/api/logout` | `Authorization: Bearer <token>` | `{message}` |

---

## 🚀 Quick Start - Server

### Start Laravel Server

```bash
cd c:\laragon\www\WOWOK
php artisan serve --host=0.0.0.0 --port=8000
```

Server akan running di: `http://127.0.0.1:8000`

---

## 📱 Quick Start - Flutter

### 1. Install Dependencies

```bash
flutter pub get
```

Pastikan di `pubspec.yaml`:
```yaml
dependencies:
  http: ^1.1.0
  shared_preferences: ^2.0.0
```

### 2. Setup BaseUrl di api_services.dart

```dart
// Untuk Android Emulator:
static const String baseUrl = "http://10.0.2.2:8000/api";

// Untuk iOS Simulator:
// static const String baseUrl = "http://localhost:8000/api";

// Untuk Device Fisik (ganti 192.168.1.100 dengan IP Anda):
// static const String baseUrl = "http://192.168.1.100:8000/api";
```

### 3. Update api_services.dart

Copy kode dari `FLUTTER_INTEGRATION_GUIDE.md` ke `lib/services/api_services.dart`

### 4. Gunakan di Widget

```dart
// Login
final result = await ApiService.login(
  email: 'user@example.com',
  password: 'password123'
);

// Get Profile
final profile = await ApiService.getProfile();

// Logout
await ApiService.logout();
```

---

## 🧪 Test API dengan cURL

### Register (PowerShell)

```powershell
$body = @{
  name="John Doe"
  email="john@example.com"
  password="password123"
  password_confirmation="password123"
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/register" `
  -Method POST `
  -Headers @{"Content-Type"="application/json"} `
  -Body $body
```

### Login (PowerShell)

```powershell
$body = @{
  email="john@example.com"
  password="password123"
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/login" `
  -Method POST `
  -Headers @{"Content-Type"="application/json"} `
  -Body $body
```

**Response akan berisi `token`. Copy token untuk test endpoint berikutnya.**

### Get Profile (PowerShell)

```powershell
$token = "PASTE_TOKEN_HERE"

Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/user" `
  -Method GET `
  -Headers @{"Authorization"="Bearer $token"}
```

### Logout (PowerShell)

```powershell
$token = "PASTE_TOKEN_HERE"

Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/logout" `
  -Method POST `
  -Headers @{"Authorization"="Bearer $token"}
```

---

## 📁 Files Modified/Created

### Modified Files:
1. **app/Http/Controllers/AuthController.php**
   - Added: `apiRegister()`, `apiLogin()`, `apiProfile()`, `apiLogout()`
   - Kept existing web methods

2. **routes/api.php**
   - Added public routes: POST `/api/register`, POST `/api/login`
   - Added protected routes: GET `/api/user`, POST `/api/logout`

3. **app/Models/User.php**
   - Added: `use HasApiTokens`
   - Enables Sanctum token generation

### Created Files:
1. **FLUTTER_API_SETUP.md** - Detailed setup guide
2. **FLUTTER_INTEGRATION_GUIDE.md** - Flutter code examples

---

## 🔐 Security Notes

⚠️ **Development Only Settings:**
- `config/cors.php` set to `allowed_origins: ['*']`
- `APP_DEBUG` enabled untuk development

🔒 **For Production:**
- Change `allowed_origins` to specific domain
- Set `APP_DEBUG=false`
- Use HTTPS only
- Add rate limiting to auth endpoints
- Implement refresh token system
- Add API logging/monitoring

---

## 🚨 Common Issues & Solutions

### "Connection refused"
→ Server Laravel tidak running. Jalankan: `php artisan serve --host=0.0.0.0 --port=8000`

### "401 Unauthorized" di Flutter
→ Token tidak dikirim. Pastikan `Authorization: Bearer <token>` di header

### "Token tidak tersimpan" di Flutter
→ Check `SharedPreferences` initialization. Lihat `FLUTTER_INTEGRATION_GUIDE.md`

### "CORS error"
→ Dev mode CORS already set to `*`. Untuk production, spesifik origin.

### "Email already exists"
→ Register dengan email berbeda atau hapus data dari database terlebih dahulu

---

## 📚 Documentation Files

| File | Deskripsi |
|------|-----------|
| `FLUTTER_API_SETUP.md` | Setup Sanctum & Laravel API |
| `FLUTTER_INTEGRATION_GUIDE.md` | Flutter integration examples |
| `FLUTTER_API_SETUP_COMPLETE.md` | File ini - ringkasan |

---

## ✨ Next Steps (Optional)

1. **Add More Endpoints:**
   - GET `/api/ruang` - List ruang
   - POST `/api/peminjaman` - Create peminjaman
   - GET `/api/peminjaman/{id}` - Detail peminjaman
   - etc.

2. **Add Validation:**
   - Request validation middleware
   - Input sanitization
   - Rate limiting

3. **Add Features:**
   - Email verification
   - Forgot password
   - Social login
   - Two-factor auth

4. **Testing:**
   - Unit tests untuk API
   - Integration tests
   - Load testing

---

## 📞 Support

Untuk error/issues:
1. Check Laravel logs: `tail -f storage/logs/laravel.log`
2. Check Flutter console output
3. Test API dengan Postman/cURL terlebih dahulu
4. Verify baseUrl dan port

---

## 🎉 Status

**✅ SETUP COMPLETE!**

Laravel API siap untuk Flutter integration. Server running dan tested!

**Next:** Configure Flutter app dengan api_services.dart dan mulai build UI.

---

**Last Updated:** November 13, 2025  
**Version:** 1.0  
**Environment:** Development (Laragon)
