<?php

namespace App\Http\Controllers;

use App\Models\Ruang;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RuangController extends Controller
{
    public function __construct()
    {
        // Pastikan user harus login untuk semua aksi di controller ini
        $this->middleware('auth');

        // Hanya admin dan petugas boleh melihat daftar dan detail ruang
        $this->middleware('role:admin,petugas')->only(['index', 'show']);

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

        // Jika permintaan dari API (JSON) kembalikan respons JSON,
        // jika dari browser kembalikan view blade untuk halaman kelola ruang.
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'List ruang berhasil diambil',
                'data' => $ruangs
            ], 200);
        }

        return view('ruang.index', ['ruang' => $ruangs]);
    }

    // GET /api/ruang/{id} - Tampilkan detail ruang
    public function show($id)
    {
        $ruang = Ruang::find($id);

        if (!$ruang) {
            return response()->json([
                'success' => false,
                'message' => 'Ruang tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ruang
        ], 200);
    }

    // POST /api/ruang - Buat ruang baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_ruang' => 'required|string|max:255',
            'kapasitas' => 'required|integer|min:1',
            'lokasi' => 'required|string'
        ]);

        $ruang = Ruang::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ruang berhasil dibuat',
            'data' => $ruang
        ], 201);
    }

    // PUT /api/ruang/{id} - Update ruang
    public function update(Request $request, $id)
    {
        $ruang = Ruang::find($id);

        if (!$ruang) {
            return response()->json([
                'success' => false,
                'message' => 'Ruang tidak ditemukan'
            ], 404);
        }

        $validated = $request->validate([
            'nama_ruang' => 'string|max:255',
            'kapasitas' => 'integer|min:1',
            'lokasi' => 'string'
        ]);

        $ruang->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ruang berhasil diupdate',
            'data' => $ruang
        ], 200);
    }

    // DELETE /api/ruang/{id} - Hapus ruang
    public function destroy($id)
    {
        $ruang = Ruang::find($id);

        if (!$ruang) {
            return response()->json([
                'success' => false,
                'message' => 'Ruang tidak ditemukan'
            ], 404);
        }

        $ruang->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ruang berhasil dihapus'
        ], 200);
    }
}
