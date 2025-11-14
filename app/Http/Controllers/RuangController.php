<?php

namespace App\Http\Controllers;

use App\Models\Ruang;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RuangController extends Controller
{
    public function __construct()
    {
        // Pastikan user harus login untuk semua aksi di controller ini
        $this->middleware('auth');

        // Jika ada pengguna dengan role 'petugas' maka izinkan both admin & petugas
        // untuk melihat daftar/detail ruang. Jika tidak ada, batasi hanya untuk admin.
        $hasPetugas = User::where('role', 'petugas')->exists();
        if ($hasPetugas) {
            // Hanya admin dan petugas boleh melihat daftar dan detail ruang
            $this->middleware('role:admin,petugas')->only(['index', 'show']);
        } else {
            // Jika role 'petugas' tidak ada, hanya admin yang boleh melihat/manage ruang
            $this->middleware('role:admin')->only(['index', 'show']);
        }

        // Hanya admin boleh membuat dan menghapus ruang
        $this->middleware('role:admin')->only(['store', 'destroy', 'update']);
    }
    // GET /api/ruang - Tampilkan semua ruang
    public function index(Request $request)
    {
        // Guard: jika user datang dari halaman jadwal via browser (referer)
        // maka jangan izinkan akses ke halaman ruang dan kembalikan ke jadwal.
        // Tetap biarkan permintaan API/JSON berjalan.
        if (! $request->wantsJson() && ! $request->is('api/*')) {
            $previous = url()->previous();
            // cek path sederhana '/peminjaman/jadwal' untuk memastikan asal
            if ($previous && Str::contains($previous, '/peminjaman/jadwal')) {
                return redirect()->route('peminjaman.jadwal')
                    ->with('error', 'Akses ke halaman ruang dari halaman Jadwal tidak diperbolehkan.');
            }
        }

        $ruangs = Ruang::all();

        // Jika permintaan API (prefix api/*) kembalikan JSON,
        // untuk permintaan web biasa kembalikan view blade.
        if ($request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'List ruang berhasil diambil',
                'data' => $ruangs
            ], 200);
        }

        return view('ruang.index', ['ruang' => $ruangs]);
    }

    // GET /api/ruang/{id} - Tampilkan detail ruang
    public function show(Request $request, $id)
    {
        $ruang = Ruang::find($id);

        if (!$ruang) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ruang tidak ditemukan'
                ], 404);
            }

            abort(404, 'Ruang tidak ditemukan');
        }

        if ($request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $ruang
            ], 200);
        }

        // For web requests, you could return a view showing room details.
        return view('ruang.show', ['ruang' => $ruang]);
    }

    // POST /api/ruang - Buat ruang baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_ruang' => 'required|string|max:255',
            'kapasitas' => 'required|integer|min:1',
            'deskripsi' => 'required|string'
        ]);

        $ruang = Ruang::create($validated);

        if ($request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Ruang berhasil dibuat',
                'data' => $ruang
            ], 201);
        }

        return redirect()->back()->with('success', 'Ruang berhasil ditambahkan!');
    }

    // PUT /api/ruang/{id} - Update ruang
    public function update(Request $request, $id)
    {
        $ruang = Ruang::find($id);

        if (!$ruang) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ruang tidak ditemukan'
                ], 404);
            }

            abort(404, 'Ruang tidak ditemukan');
        }

        $validated = $request->validate([
            'nama_ruang' => 'string|max:255',
            'kapasitas' => 'integer|min:1',
            'deskripsi' => 'string'
        ]);

        $ruang->update($validated);

        if ($request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Ruang berhasil diupdate',
                'data' => $ruang
            ], 200);
        }

        return redirect()->back()->with('success', 'Ruang berhasil diupdate');
    }

    // DELETE /api/ruang/{id} - Hapus ruang
    public function destroy(Request $request, $id)
    {
        $ruang = Ruang::find($id);

        if (!$ruang) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ruang tidak ditemukan'
                ], 404);
            }

            abort(404, 'Ruang tidak ditemukan');
        }

        $ruang->delete();

        if ($request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Ruang berhasil dihapus'
            ], 200);
        }

        return redirect()->back()->with('success', 'Ruang berhasil dihapus');
    }
}
