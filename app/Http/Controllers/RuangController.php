<?php

namespace App\Http\Controllers;

use App\Models\Ruang;
use Illuminate\Http\Request;

class RuangController extends Controller
{
    // GET /api/ruang - Tampilkan semua ruang
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'List ruang berhasil diambil',
            'data' => Ruang::all()
        ], 200);
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
