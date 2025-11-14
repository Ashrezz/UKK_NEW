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
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'pengunjung',
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

        $user = User::where('email', $identifier)->orWhere('username', $identifier)->first();

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

        // Try username
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
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username|min:3|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'pengunjung',
        ]);

        return redirect()->route('login')->with('success', 'Registrasi berhasil, silakan login');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
