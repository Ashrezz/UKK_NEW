<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ==================== API Methods (untuk Flutter) ====================

    /**
     * Register user baru (API)
     */
    public function apiRegister(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'no_hp' => 'required|string|min:8|max:30',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.min' => 'Password harus minimal 8 karakter',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['name'], // Also set username
            'email' => $validated['email'],
            'no_hp' => $validated['no_hp'],
            'password' => Hash::make($validated['password']),
            'role' => 'user', // Changed from 'pengunjung' to 'user' to match database enum
        ]);

        // Buat token Sanctum
        $token = $user->createToken('mobile-token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ], 201);
    }

    /**
     * Login user (API)
     */
    public function apiLogin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $identifier = $validated['email'];
        $password = $validated['password'];

        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->orWhere('name', $identifier)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Username/Email atau password salah'],
            ]);
        }

        // Buat token Sanctum
        $token = $user->createToken('mobile-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ]);
    }

    /**
     * Get profil user (API) - protected route
     */
    public function apiProfile(Request $request)
    {
        return response()->json([
            'message' => 'Profil user',
            'data' => $request->user()
        ]);
    }

    /**
     * Logout user (API) - protected route
     */
    public function apiLogout(Request $request)
    {
        // Hapus token yang dipakai saat ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    // ==================== Web Methods (untuk Web/Browser) ====================

    public function loginForm()
    {
        return view('auth.login');
    }
    public function login(Request $request)
    {
        $identifier = $request->input('email');
        $password = $request->input('password');

        // Try login dengan email atau username
        if ($this->attemptLogin($identifier, $password)) {
            return redirect()->route('home');
        }

        return back()->with('error', 'Username/Email atau password salah');
    }

    // Helper: Try login with username or email
    private function attemptLogin($identifier, $password)
    {
        // Try email first
        if (Auth::attempt(['email' => $identifier, 'password' => $password])) {
            return true;
        }

        // Try username field
        if (Auth::attempt(['username' => $identifier, 'password' => $password])) {
            return true;
        }

        return false;
    }
    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username|min:3|max:255',
            'email' => 'required|email|unique:users,email',
            'no_hp' => 'required|string|min:8|max:30',
            'password' => 'required|min:8|confirmed',
        ], [
            'password.min' => 'Password harus minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
            'username.unique' => 'Username sudah digunakan',
            'email.unique' => 'Email sudah terdaftar',
        ]);

        User::create([
            'name' => $request->username,
            'username' => $request->username,
            'email' => $request->email,
            'no_hp' => $request->no_hp,
            'password' => Hash::make($request->password),
            'role' => 'user', // Changed from 'pengunjung' to 'user' to match database enum
        ]);

        return redirect()->route('login')->with('success', 'Registrasi berhasil, silakan login');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }

    // ==================== Password Reset Methods ====================

    /**
     * Show password reset request form
     */
    public function resetRequestForm()
    {
        return view('auth.password-reset-request');
    }

    /**
     * Send verification code to email
     */
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Email tidak terdaftar dalam sistem',
        ]);

        $email = $request->email;

        // Generate 6-digit verification code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store code in database (expires in 15 minutes)
        \DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => $code,
            'expires_at' => now()->addMinutes(15),
            'used' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send email with verification code
        try {
            \Mail::raw("Kode verifikasi reset password Anda adalah: {$code}\n\nKode ini berlaku selama 15 menit.", function ($message) use ($email) {
                $message->to($email)
                    ->subject('Kode Verifikasi Reset Password');
            });

            // Store email in session for next step
            session(['reset_email' => $email]);

            return redirect()->route('password.verify')->with('success', 'Kode verifikasi telah dikirim ke email Anda');
        } catch (\Exception $e) {
            \Log::error('Failed to send reset code email', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal mengirim email. Silakan coba lagi atau hubungi admin via WhatsApp.');
        }
    }

    /**
     * Show verification code form
     */
    public function verifyCodeForm()
    {
        if (!session('reset_email')) {
            return redirect()->route('password.request')->with('error', 'Silakan masukkan email terlebih dahulu');
        }

        return view('auth.password-verify-code');
    }

    /**
     * Verify the code
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $email = session('reset_email');
        if (!$email) {
            return redirect()->route('password.request')->with('error', 'Sesi expired. Silakan mulai dari awal.');
        }

        // Check if code is valid
        $token = \DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $request->code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$token) {
            return back()->with('error', 'Kode verifikasi salah atau sudah kadaluarsa');
        }

        // Store verified token in session
        session(['verified_token_id' => $token->id]);

        return redirect()->route('password.reset.new')->with('success', 'Kode verifikasi berhasil. Silakan masukkan password baru.');
    }

    /**
     * Show new password form
     */
    public function newPasswordForm()
    {
        if (!session('verified_token_id')) {
            return redirect()->route('password.request')->with('error', 'Verifikasi diperlukan');
        }

        return view('auth.password-reset-new');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ], [
            'password.min' => 'Password harus minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        $tokenId = session('verified_token_id');
        if (!$tokenId) {
            return redirect()->route('password.request')->with('error', 'Sesi expired. Silakan mulai dari awal.');
        }

        // Get token info
        $token = \DB::table('password_reset_tokens')->where('id', $tokenId)->first();
        if (!$token || $token->used) {
            return redirect()->route('password.request')->with('error', 'Token tidak valid');
        }

        // Update user password
        $user = User::where('email', $token->email)->first();
        if (!$user) {
            return redirect()->route('password.request')->with('error', 'User tidak ditemukan');
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Mark token as used
        \DB::table('password_reset_tokens')
            ->where('id', $tokenId)
            ->update(['used' => true, 'updated_at' => now()]);

        // Clear session
        session()->forget(['reset_email', 'verified_token_id']);

        return redirect()->route('login')->with('success', 'Password berhasil direset. Silakan login dengan password baru.');
    }
}
