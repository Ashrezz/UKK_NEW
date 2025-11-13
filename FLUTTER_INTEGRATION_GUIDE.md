# 🚀 Integrasi Flutter ke Laravel API

Panduan lengkap cara mengintegrasikan Flutter app dengan Laravel API yang sudah setup.

---

## 📱 Setup Flutter (api_services.dart)

Update file `lib/services/api_services.dart` di project Flutter Anda dengan kode di bawah.

### Untuk Android Emulator:
```dart
static const String baseUrl = "http://10.0.2.2:8000/api";
```

### Untuk iOS Simulator:
```dart
static const String baseUrl = "http://localhost:8000/api";
```

### Untuk Device Fisik (ganti IP sesuai mesin Anda):
```dart
static const String baseUrl = "http://192.168.1.100:8000/api";
```

---

## 📋 API Service Class (api_services.dart)

Berikut adalah contoh lengkap service class untuk berkomunikasi dengan Laravel API:

```dart
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';

class ApiService {
  // ⚠️ Sesuaikan baseUrl dengan environment Anda
  static const String baseUrl = "http://10.0.2.2:8000/api"; // Android Emulator
  
  // Atau gunakan ini untuk iOS Simulator:
  // static const String baseUrl = "http://localhost:8000/api";
  
  // Atau gunakan ini untuk Device Fisik (ganti 192.168.1.100 dengan IP Anda):
  // static const String baseUrl = "http://192.168.1.100:8000/api";

  static final http.Client _client = http.Client();
  static final _prefs = SharedPreferences.getInstance();

  // ==================== Token Management ====================

  /// Simpan token ke SharedPreferences
  static Future<void> _saveToken(String token) async {
    final prefs = await _prefs;
    await prefs.setString('auth_token', token);
  }

  /// Ambil token dari SharedPreferences
  static Future<String?> getToken() async {
    final prefs = await _prefs;
    return prefs.getString('auth_token');
  }

  /// Hapus token dari SharedPreferences
  static Future<void> clearToken() async {
    final prefs = await _prefs;
    await prefs.remove('auth_token');
  }

  /// Check apakah user sudah login (ada token)
  static Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }

  // ==================== Headers ====================

  /// Header untuk request biasa (dengan token jika ada)
  static Future<Map<String, String>> _getHeaders({bool includeAuth = true}) async {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    if (includeAuth) {
      final token = await getToken();
      if (token != null) {
        headers['Authorization'] = 'Bearer $token';
      }
    }

    return headers;
  }

  // ==================== Auth Endpoints ====================

  /// Register user baru
  static Future<Map<String, dynamic>?> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    try {
      final response = await _client.post(
        Uri.parse('$baseUrl/register'),
        headers: await _getHeaders(includeAuth: false),
        body: jsonEncode({
          'name': name,
          'email': email,
          'password': password,
          'password_confirmation': passwordConfirmation,
        }),
      ).timeout(Duration(seconds: 30));

      if (response.statusCode == 201 || response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        // Simpan token jika ada
        if (data['data']?['token'] != null) {
          await _saveToken(data['data']['token']);
        }

        return data;
      } else if (response.statusCode == 422) {
        // Validation error
        final errorData = jsonDecode(response.body);
        print('Validation Error: ${errorData['errors']}');
        return null;
      } else {
        print('Register Error: ${response.statusCode}');
        return null;
      }
    } catch (e) {
      print('Register Exception: $e');
      return null;
    }
  }

  /// Login dengan email dan password
  static Future<Map<String, dynamic>?> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await _client.post(
        Uri.parse('$baseUrl/login'),
        headers: await _getHeaders(includeAuth: false),
        body: jsonEncode({
          'email': email,
          'password': password,
        }),
      ).timeout(Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        // Simpan token jika ada
        if (data['data']?['token'] != null) {
          await _saveToken(data['data']['token']);
        }

        return data;
      } else {
        print('Login Error: ${response.statusCode} - ${response.body}');
        return null;
      }
    } catch (e) {
      print('Login Exception: $e');
      return null;
    }
  }

  /// Get profil user saat ini (protected route)
  static Future<Map<String, dynamic>?> getProfile() async {
    try {
      final response = await _client.get(
        Uri.parse('$baseUrl/user'),
        headers: await _getHeaders(includeAuth: true),
      ).timeout(Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data;
      } else if (response.statusCode == 401) {
        // Token expired atau invalid
        await clearToken();
        print('Token expired. Silakan login lagi.');
        return null;
      } else {
        print('Get Profile Error: ${response.statusCode}');
        return null;
      }
    } catch (e) {
      print('Get Profile Exception: $e');
      return null;
    }
  }

  /// Logout dan revoke token (protected route)
  static Future<bool> logout() async {
    try {
      final response = await _client.post(
        Uri.parse('$baseUrl/logout'),
        headers: await _getHeaders(includeAuth: true),
      ).timeout(Duration(seconds: 30));

      if (response.statusCode == 200) {
        // Hapus token dari storage
        await clearToken();
        return true;
      } else {
        print('Logout Error: ${response.statusCode}');
        return false;
      }
    } catch (e) {
      print('Logout Exception: $e');
      return false;
    }
  }

  // ==================== Other API Endpoints ====================

  /// Get jadwal peminjaman berdasarkan tanggal
  static Future<List<dynamic>?> getJadwalByDate(String date) async {
    try {
      final response = await _client.get(
        Uri.parse('$baseUrl/peminjaman/jadwal/$date'),
        headers: await _getHeaders(),
      ).timeout(Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['data'] ?? [];
      } else {
        print('Get Jadwal Error: ${response.statusCode}');
        return null;
      }
    } catch (e) {
      print('Get Jadwal Exception: $e');
      return null;
    }
  }

  /// Get detail peminjaman
  static Future<Map<String, dynamic>?> getPeminjamanDetail(int id) async {
    try {
      final response = await _client.get(
        Uri.parse('$baseUrl/peminjaman/$id'),
        headers: await _getHeaders(),
      ).timeout(Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['data'];
      } else {
        print('Get Peminjaman Detail Error: ${response.statusCode}');
        return null;
      }
    } catch (e) {
      print('Get Peminjaman Detail Exception: $e');
      return null;
    }
  }
}
```

---

## 💻 Contoh Penggunaan di UI Flutter

### Contoh 1: Login Widget

```dart
import 'package:flutter/material.dart';
import 'services/api_services.dart';

class LoginScreen extends StatefulWidget {
  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final emailController = TextEditingController();
  final passwordController = TextEditingController();
  bool isLoading = false;

  @override
  void dispose() {
    emailController.dispose();
    passwordController.dispose();
    super.dispose();
  }

  void _handleLogin() async {
    if (emailController.text.isEmpty || passwordController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Email dan password harus diisi')),
      );
      return;
    }

    setState(() => isLoading = true);

    final result = await ApiService.login(
      email: emailController.text,
      password: passwordController.text,
    );

    setState(() => isLoading = false);

    if (result != null) {
      // Login berhasil, arahkan ke home
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Login berhasil!')),
      );
      // Navigator.pushReplacementNamed(context, '/home');
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Login gagal, cek email/password')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Login')),
      body: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            TextField(
              controller: emailController,
              decoration: InputDecoration(
                labelText: 'Email',
                border: OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 16),
            TextField(
              controller: passwordController,
              obscureText: true,
              decoration: InputDecoration(
                labelText: 'Password',
                border: OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 24),
            isLoading
                ? CircularProgressIndicator()
                : ElevatedButton(
                    onPressed: _handleLogin,
                    child: Text('Login'),
                    style: ElevatedButton.styleFrom(
                      minimumSize: Size(double.infinity, 50),
                    ),
                  ),
          ],
        ),
      ),
    );
  }
}
```

### Contoh 2: Profile Screen

```dart
import 'package:flutter/material.dart';
import 'services/api_services.dart';

class ProfileScreen extends StatefulWidget {
  @override
  _ProfileScreenState createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  late Future<Map<String, dynamic>?> _profileFuture;

  @override
  void initState() {
    super.initState();
    _profileFuture = ApiService.getProfile();
  }

  void _handleLogout() async {
    final result = await ApiService.logout();
    
    if (result) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Logout berhasil')),
      );
      // Navigator.pushReplacementNamed(context, '/login');
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Logout gagal')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Profil'),
        actions: [
          IconButton(
            icon: Icon(Icons.logout),
            onPressed: _handleLogout,
          ),
        ],
      ),
      body: FutureBuilder<Map<String, dynamic>?>(
        future: _profileFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError || snapshot.data == null) {
            return Center(child: Text('Gagal memuat profil'));
          }

          final user = snapshot.data!;

          return Padding(
            padding: EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Nama: ${user['name'] ?? '-'}'),
                SizedBox(height: 8),
                Text('Email: ${user['email'] ?? '-'}'),
                SizedBox(height: 8),
                Text('Role: ${user['role'] ?? '-'}'),
              ],
            ),
          );
        },
      ),
    );
  }
}
```

### Contoh 3: Register Widget

```dart
import 'package:flutter/material.dart';
import 'services/api_services.dart';

class RegisterScreen extends StatefulWidget {
  @override
  _RegisterScreenState createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final nameController = TextEditingController();
  final emailController = TextEditingController();
  final passwordController = TextEditingController();
  final passwordConfirmController = TextEditingController();
  bool isLoading = false;

  @override
  void dispose() {
    nameController.dispose();
    emailController.dispose();
    passwordController.dispose();
    passwordConfirmController.dispose();
    super.dispose();
  }

  void _handleRegister() async {
    if (nameController.text.isEmpty ||
        emailController.text.isEmpty ||
        passwordController.text.isEmpty ||
        passwordConfirmController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Semua field harus diisi')),
      );
      return;
    }

    if (passwordController.text != passwordConfirmController.text) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Password tidak cocok')),
      );
      return;
    }

    setState(() => isLoading = true);

    final result = await ApiService.register(
      name: nameController.text,
      email: emailController.text,
      password: passwordController.text,
      passwordConfirmation: passwordConfirmController.text,
    );

    setState(() => isLoading = false);

    if (result != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Registrasi berhasil! Silakan login.')),
      );
      // Navigator.pushReplacementNamed(context, '/login');
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Registrasi gagal')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Register')),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(
              controller: nameController,
              decoration: InputDecoration(
                labelText: 'Nama',
                border: OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 16),
            TextField(
              controller: emailController,
              decoration: InputDecoration(
                labelText: 'Email',
                border: OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 16),
            TextField(
              controller: passwordController,
              obscureText: true,
              decoration: InputDecoration(
                labelText: 'Password',
                border: OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 16),
            TextField(
              controller: passwordConfirmController,
              obscureText: true,
              decoration: InputDecoration(
                labelText: 'Konfirmasi Password',
                border: OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 24),
            isLoading
                ? CircularProgressIndicator()
                : ElevatedButton(
                    onPressed: _handleRegister,
                    child: Text('Register'),
                    style: ElevatedButton.styleFrom(
                      minimumSize: Size(double.infinity, 50),
                    ),
                  ),
          ],
        ),
      ),
    );
  }
}
```

---

## 📦 Dependencies yang Dibutuhkan (pubspec.yaml)

```yaml
dependencies:
  flutter:
    sdk: flutter
  http: ^1.1.0
  shared_preferences: ^2.0.0
```

Install dengan:
```bash
flutter pub get
```

---

## 🔍 Troubleshooting

### ❌ Error: "Connection refused"
- **Penyebab:** Server Laravel tidak running
- **Solusi:** Pastikan jalankan `php artisan serve --host=0.0.0.0 --port=8000`

### ❌ Error: "Cannot connect to host"
- **Penyebab:** BaseUrl salah
- **Solusi:** 
  - Android Emulator: `http://10.0.2.2:8000/api`
  - iOS Simulator: `http://localhost:8000/api`
  - Device Fisik: `http://192.168.X.X:8000/api` (ganti IP)

### ❌ Error: "401 Unauthorized"
- **Penyebab:** Token tidak dikirim atau expired
- **Solusi:** 
  - Cek apakah header `Authorization: Bearer <token>` dikirim
  - Coba login lagi untuk dapatkan token baru
  - Lihat `api_services.dart` bagian `_getHeaders()`

### ❌ Error: "422 Unprocessable Entity"
- **Penyebab:** Validation error dari server
- **Solusi:** Cek error messages di response, biasanya ada field yang tidak valid

### ❌ Error: Token tidak tersimpan di SharedPreferences
- **Penyebab:** SharedPreferences tidak diinisialisasi
- **Solusi:** Pastikan `await SharedPreferences.getInstance()` berhasil di app Anda

---

## ✅ Testing Checklist

- [ ] Laravel server running di `127.0.0.1:8000`
- [ ] CORS config sudah enable
- [ ] Register endpoint bisa diakses
- [ ] Login endpoint mengembalikan token
- [ ] Get profile endpoint berfungsi dengan token
- [ ] Logout endpoint membatalkan token
- [ ] Flutter `api_services.dart` sudah diupdate
- [ ] BaseUrl sudah disesuaikan untuk environment Anda
- [ ] `http` dan `shared_preferences` package installed
- [ ] Login dari Flutter berhasil dan token tersimpan
- [ ] Get profile dari Flutter berhasil

---

**Jika masih ada error, cek Laravel logs:**
```bash
tail -f storage/logs/laravel.log
```

Dan cek Flutter console output saat run app. Happy coding! 🚀
