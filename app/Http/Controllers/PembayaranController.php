<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PembayaranController extends Controller
{
    public function uploadBukti(Request $request, $id)
    {
        $request->validate([
            'bukti_pembayaran' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $peminjaman = Peminjaman::findOrFail($id);

        if ($request->hasFile('bukti_pembayaran')) {
            // Hapus file lama jika ada
            if ($peminjaman->bukti_pembayaran && strpos($peminjaman->bukti_pembayaran, 'bukti_pembayaran/') === 0) {
                try {
                    Storage::disk('public')->delete($peminjaman->bukti_pembayaran);
                } catch (\Throwable $e) {
                    // ignore deletion errors
                }
            }

            // Simpan file baru ke public disk dengan relative path
            $file = $request->file('bukti_pembayaran');
            $filename = time() . '_' . $file->getClientOriginalName();
            $relativePath = $file->storeAs('bukti_pembayaran', $filename, 'public');

            // Update database dengan path relative dalam public disk
            $peminjaman->update([
                'bukti_pembayaran' => $relativePath,
                'status_pembayaran' => 'menunggu_verifikasi',
                'waktu_pembayaran' => now()
            ]);

            return back()->with('success', 'Bukti pembayaran berhasil diunggah dan menunggu verifikasi admin.');
        }

        return back()->with('error', 'Terjadi kesalahan saat mengunggah bukti pembayaran.');
    }

    public function verifikasiIndex()
    {
        $peminjaman = Peminjaman::with(['ruang', 'user'])
            ->where('status_pembayaran', 'menunggu_verifikasi')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pembayaran.verifikasi', compact('peminjaman'));
    }

    /**
     * Serve a bukti pembayaran file from the public disk.
     * This avoids depending on the public/storage symlink being present on the server.
     */
    public function showBukti($filename)
    {
        // sanitize filename to avoid traversal
        $filename = basename($filename);

        $candidates = [
            $filename,
            'bukti_pembayaran/' . $filename,
        ];

        foreach ($candidates as $candidate) {
            if (Storage::disk('public')->exists($candidate)) {
                // Construct expected local path for public disk (storage/app/public/...)
                $full = storage_path('app/public/' . $candidate);
                if (file_exists($full)) {
                    return response()->file($full);
                }
                // As a fallback, attempt to read via Storage and stream
                try {
                    $stream = Storage::disk('public')->get($candidate);
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->buffer($stream) ?: 'application/octet-stream';
                    return response($stream, 200, ['Content-Type' => $mime]);
                } catch (\Throwable $e) {
                    // continue to next candidate or abort
                }
            }
        }

        abort(404, 'File not found');
    }

    public function verifikasi($id)
    {
        $peminjaman = Peminjaman::findOrFail($id);

        $peminjaman->update([
            'status_pembayaran' => 'terverifikasi',
            'status' => 'disetujui',
            'waktu_pembayaran' => $peminjaman->waktu_pembayaran ?? now()
        ]);

        return back()->with('success', 'Pembayaran telah diverifikasi dan peminjaman disetujui.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'ruang_id' => 'required',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'keperluan' => 'required',
            'bukti_pembayaran' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ], [
            'bukti_pembayaran.required' => 'Bukti pembayaran harus diunggah berupa file gambar (jpg, png)'
        ]);

        // Calculate booking duration and cost
        $mulai = strtotime($request->jam_mulai);
        $selesai = strtotime($request->jam_selesai);
        $durasi = ceil(($selesai - $mulai) / 3600); // Duration in hours
        $biaya = $durasi * 50000; // Rp. 50.000 per hour

        // Simpan file ke public disk dengan relative path
        $file = $request->file('bukti_pembayaran');
        $filename = time() . '_' . $file->getClientOriginalName();
        $relativePath = $file->storeAs('bukti_pembayaran', $filename, 'public');

        $peminjaman = Peminjaman::create([
            'user_id' => auth()->id(),
            'ruang_id' => $request->ruang_id,
            'tanggal' => $request->tanggal,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'keperluan' => $request->keperluan,
            'status' => 'pending',
            'biaya' => $biaya,
            'status_pembayaran' => 'menunggu_verifikasi',
            'bukti_pembayaran' => $relativePath,
            'waktu_pembayaran' => now()
        ]);

        return redirect()->route('home')->with('success', 'Pengajuan peminjaman berhasil dibuat!');
    }
}
