<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    // READ: List all users
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    // CREATE: Show form
    public function create()
    {
        return view('admin.tambah_user');
    }

    // CREATE: Store new user
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username|min:3|max:255',
            'email' => 'required|email|unique:users,email',
            'no_hp' => 'required|string|min:8|max:30',
            'password' => 'required|string|min:8',
            'role' => 'required|in:petugas,user',
        ], [
            'password.min' => 'Password harus minimal 8 karakter',
        ]);

        User::create([
            'name' => $request->username,
            'username' => $request->username,
            'email' => $request->email,
            'no_hp' => $request->no_hp,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan!');
    }

    // UPDATE: Show edit form
    public function edit($id)
    {
        $user = User::findOrFail($id);

        // Prevent editing admin account
        if ($user->role === 'admin' && auth()->id() !== $user->id) {
            return redirect()->route('admin.users.index')->with('error', 'Tidak dapat mengedit akun admin lain!');
        }

        return view('admin.users.edit', compact('user'));
    }

    // UPDATE: Update user
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent editing admin account
        if ($user->role === 'admin' && auth()->id() !== $user->id) {
            return redirect()->route('admin.users.index')->with('error', 'Tidak dapat mengedit akun admin lain!');
        }

        $request->validate([
            'username' => 'required|string|min:3|max:255|unique:users,username,' . $id,
            'email' => 'required|email|unique:users,email,' . $id,
            'no_hp' => 'required|string|min:8|max:30',
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:admin,petugas,user',
        ], [
            'password.min' => 'Password harus minimal 8 karakter',
        ]);

        $data = [
            'name' => $request->username,
            'username' => $request->username,
            'email' => $request->email,
            'no_hp' => $request->no_hp,
            'role' => $request->role,
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diupdate!');
    }

    // DELETE: Delete user
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting admin account
        if ($user->role === 'admin') {
            return redirect()->route('admin.users.index')->with('error', 'Tidak dapat menghapus akun admin!');
        }

        // Prevent deleting own account
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', 'Tidak dapat menghapus akun sendiri!');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus!');
    }
}
