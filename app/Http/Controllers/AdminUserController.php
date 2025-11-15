<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function create()
    {
        return view('admin.tambah_user');
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username|min:3|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:petugas,pengunjung',
        ]);

        User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return redirect()->route('admin.tambah_user.create')->with('success', 'User berhasil ditambahkan!');
    }
}
