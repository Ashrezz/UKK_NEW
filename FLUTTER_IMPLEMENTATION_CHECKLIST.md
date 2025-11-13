# ✅ Flutter Implementation Checklist

Checklist step-by-step untuk mengintegrasikan Flutter dengan Laravel API.

---

## 📱 Phase 1: Setup Flutter Project

- [ ] Flutter project sudah created
- [ ] Running `flutter pub get` berhasil
- [ ] Dependencies di pubspec.yaml:
  ```yaml
  http: ^1.1.0
  shared_preferences: ^2.0.0
  ```

---

## 🔧 Phase 2: Setup Services

### API Service
- [ ] Create file: `lib/services/api_services.dart`
- [ ] Copy isi dari: `api_services.dart.example` (di Laravel project)
- [ ] Update baseUrl sesuai platform:
  - [ ] Android Emulator: `http://10.0.2.2:8000/api`
  - [ ] iOS Simulator: `http://localhost:8000/api`
  - [ ] Device Fisik: `http://192.168.X.X:8000/api`

### SharedPreferences Initialization
- [ ] Add di main.dart:
  ```dart
  void main() async {
    WidgetsFlutterBinding.ensureInitialized();
    await SharedPreferences.getInstance();
    runApp(MyApp());
  }
  ```

---

## 🎨 Phase 3: Create UI Screens

### Login Screen
- [ ] Create: `lib/screens/login_screen.dart`
- [ ] UI elements:
  - [ ] Email TextField
  - [ ] Password TextField
  - [ ] Login button
  - [ ] Register link
  - [ ] Loading indicator
- [ ] Functionality:
  - [ ] Call `ApiService.login()`
  - [ ] Save token on success
  - [ ] Show error on failure
  - [ ] Navigate to home on success

### Register Screen
- [ ] Create: `lib/screens/register_screen.dart`
- [ ] UI elements:
  - [ ] Name TextField
  - [ ] Email TextField
  - [ ] Password TextField
  - [ ] Confirm Password TextField
  - [ ] Register button
  - [ ] Login link
  - [ ] Loading indicator
- [ ] Functionality:
  - [ ] Validate input
  - [ ] Call `ApiService.register()`
  - [ ] Save token on success
  - [ ] Navigate to login/home on success

### Profile Screen
- [ ] Create: `lib/screens/profile_screen.dart`
- [ ] UI elements:
  - [ ] Display user name
  - [ ] Display user email
  - [ ] Display user role
  - [ ] Logout button
- [ ] Functionality:
  - [ ] Call `ApiService.getProfile()`
  - [ ] Display user data
  - [ ] Call `ApiService.logout()` on logout
  - [ ] Navigate to login after logout

### Home Screen
- [ ] Create: `lib/screens/home_screen.dart`
- [ ] Check login status:
  - [ ] `ApiService.isLoggedIn()` → show profile
  - [ ] Not logged in → show login screen

---

## 🧪 Phase 4: Testing

### Manual Testing in Flutter

#### Login Flow
- [ ] Open app
- [ ] Navigate to register
- [ ] Enter email, password
- [ ] Click register
- [ ] Token saved? Check Xcode/Android Studio or use inspection
- [ ] Navigated to home/profile? 
- [ ] Close and reopen app
- [ ] Still logged in (token persisted)?

#### Profile Flow
- [ ] On profile screen, data loaded?
- [ ] Name, email, role displayed?
- [ ] Click logout
- [ ] Token cleared?
- [ ] Redirected to login?
- [ ] Can't access profile without login?

#### Login Again
- [ ] Open login screen
- [ ] Enter credentials
- [ ] Click login
- [ ] Token saved?
- [ ] Profile accessible?

### API Testing (Before Flutter)

- [ ] Register works: `POST /api/register`
  ```powershell
  # Should return 201 with token
  ```

- [ ] Login works: `POST /api/login`
  ```powershell
  # Should return 200 with token
  ```

- [ ] Get Profile works: `GET /api/user` with token
  ```powershell
  # Should return 200 with user data
  ```

- [ ] Logout works: `POST /api/logout` with token
  ```powershell
  # Should return 200 and invalidate token
  ```

---

## 🐛 Phase 5: Debugging

### If Login Not Working

Check:
- [ ] API server running? `php artisan serve --host=0.0.0.0 --port=8000`
- [ ] baseUrl correct di api_services.dart?
- [ ] Email/password correct?
- [ ] Check Laravel logs: `storage/logs/laravel.log`
- [ ] Check Flutter console output
- [ ] Try with Postman/cURL first

### If Token Not Saving

Check:
- [ ] SharedPreferences initialized in main.dart?
- [ ] `_saveToken()` being called in ApiService?
- [ ] Check `_prefs.setString()` returning true?
- [ ] Try restart app

### If Profile Not Loading

Check:
- [ ] Token exists? `getToken()` returning non-null?
- [ ] Authorization header added? `'Bearer $token'`
- [ ] Token valid? Try login again
- [ ] Check 401 error (means token invalid/expired)

### If Logout Not Working

Check:
- [ ] Token sent with request?
- [ ] Check Laravel `/api/logout` endpoint
- [ ] Token cleared from SharedPreferences?
- [ ] Redirected to login?

---

## 📦 Phase 6: Deployment

### Before Production

- [ ] Change baseUrl ke domain production
- [ ] Update CORS di Laravel (specific origins)
- [ ] Set APP_DEBUG=false di Laravel
- [ ] Test all endpoints di production server
- [ ] Add error handling & user-friendly messages
- [ ] Test network errors & timeouts
- [ ] Test offline mode (if needed)

### Build APK/IPA

- [ ] Android:
  ```bash
  flutter build apk --release
  ```

- [ ] iOS:
  ```bash
  flutter build ipa --release
  ```

---

## 📋 Code Files Needed

Create di Flutter project:

```
lib/
├── main.dart                              (update)
├── screens/
│   ├── login_screen.dart                 (create)
│   ├── register_screen.dart              (create)
│   ├── profile_screen.dart               (create)
│   ├── home_screen.dart                  (create)
│   └── splash_screen.dart                (optional)
├── services/
│   └── api_services.dart                 (copy from api_services.dart.example)
├── models/
│   └── user_model.dart                   (optional)
├── widgets/
│   ├── custom_button.dart                (optional)
│   ├── custom_text_field.dart            (optional)
│   └── loading_dialog.dart               (optional)
└── utils/
    ├── constants.dart                    (optional)
    └── validators.dart                   (optional)
```

---

## 🎯 Success Criteria

✅ All of the following working:

1. [ ] Register new user via Flutter app
2. [ ] Token saved to SharedPreferences
3. [ ] Login with saved credentials
4. [ ] Token automatically included in requests
5. [ ] Get profile data from API
6. [ ] Logout clears token
7. [ ] After logout, can't access profile (redirected to login)
8. [ ] Restart app, still logged in (token persisted)
9. [ ] All UI screens working
10. [ ] Error messages displayed to user
11. [ ] Loading states shown during API calls

---

## 💡 Tips

- Use `print()` atau `debugPrint()` di ApiService untuk debugging
- Check Flutter console → Network tab untuk melihat requests
- Test API dengan Postman/cURL sebelum Flutter
- Add try-catch di semua API calls
- Show loading indicator saat API call
- Validate input sebelum send ke API
- Handle network errors gracefully
- Show user-friendly error messages

---

## 📞 Support

Jika stuck:

1. Check Console/Logs
   - Flutter console output
   - Laravel logs: `tail -f storage/logs/laravel.log`
   - Device logs (Android/iOS)

2. Test with Postman/cURL
   - Validate API responses
   - Check headers & body

3. Check Documentation
   - FLUTTER_INTEGRATION_GUIDE.md
   - FLUTTER_API_SETUP.md
   - api_services.dart.example

4. Common Issues
   - Connection refused → Server not running
   - 401 → Token invalid/not sent
   - 422 → Validation error
   - CORS → Check config/cors.php

---

## 📅 Estimated Timeline

- **Day 1-2:** Setup & understand architecture
- **Day 2-3:** Create basic UI screens
- **Day 3-4:** Integrate with API
- **Day 4-5:** Testing & debugging
- **Day 5+:** Polish & deployment

---

**Start:** Phase 1 → Phase 2 → Phase 3 → Phase 4 → Phase 5 → Phase 6 ✅

Good luck! 🚀
