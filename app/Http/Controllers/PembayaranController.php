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
            'bukti_pembayaran' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $peminjaman = Peminjaman::findOrFail($id);

        if ($request->hasFile('bukti_pembayaran')) {
            $file = $request->file('bukti_pembayaran');
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());

            // ✅ PRIMARY: Simpan ke BLOB database untuk persistence di Railway
            try {
                $contents = file_get_contents($file->getRealPath());
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->buffer($contents) ?: ($file->getClientMimeType() ?? 'image/jpeg');

                $peminjaman->bukti_pembayaran_blob = $contents;
                $peminjaman->bukti_pembayaran_mime = $mime;
                $peminjaman->bukti_pembayaran_name = $filename;
                $peminjaman->bukti_pembayaran_size = strlen($contents);

                $this->info("✓ Saved to BLOB: {$filename} (" . round(strlen($contents) / 1024, 2) . "KB)");
            } catch (\Throwable $e) {
                \Log::error("Failed to save BLOB for peminjaman {$id}: " . $e->getMessage());
                return back()->with('error', 'Gagal menyimpan bukti pembayaran ke database.');
            }

            // SECONDARY: Simpan ke file storage sebagai backup (optional)
            try {
                $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
                $relativePath = $file->storeAs('bukti_pembayaran', $filename, $disk);

                // Jika local disk, juga copy ke public folder
                if ($disk === 'public') {
                    $publicDir = public_path('bukti_pembayaran');
                    if (!is_dir($publicDir)) {
                        mkdir($publicDir, 0755, true);
                    }
                    $storedFull = storage_path('app/public/' . $relativePath);
                    $publicFull = $publicDir . DIRECTORY_SEPARATOR . $filename;
                    if (file_exists($storedFull)) {
                        copy($storedFull, $publicFull);
                    }
                }
            } catch (\Throwable $e) {
                // File storage backup gagal, tapi tidak masalah karena BLOB sudah tersimpan
                \Log::warning("Optional file storage backup failed: " . $e->getMessage());
            }

            // Update status pembayaran
            $peminjaman->update([
                'status_pembayaran' => 'menunggu_verifikasi',
                'waktu_pembayaran' => now(),
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
     * Serve bukti pembayaran dari BLOB database (PRIMARY)
     * Fallback ke file storage jika BLOB tidak tersedia
     */
    public function showBukti($filename)
    {
        // Sanitize filename
        $filename = basename($filename);

        // ✅ PRIMARY: Coba serve dari BLOB database
        // Cari record berdasarkan bukti_pembayaran_name atau ID
        $peminjaman = Peminjaman::where('bukti_pembayaran_name', $filename)
            ->orWhere('id', preg_replace('/[^0-9]/', '', $filename))
            ->whereNotNull('bukti_pembayaran_blob')
            ->first();

        if ($peminjaman && !empty($peminjaman->bukti_pembayaran_blob)) {
            $blob = $peminjaman->bukti_pembayaran_blob;
            $mime = $peminjaman->bukti_pembayaran_mime ?? 'image/jpeg';

            return response($blob, 200)
                ->header('Content-Type', $mime)
                ->header('Content-Length', strlen($blob))
                ->header('Cache-Control', 'public, max-age=3600');
        }

        // FALLBACK: Coba serve dari file storage
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';

        $candidates = [
            $filename,
            'bukti_pembayaran/' . $filename,
        ];

        foreach ($candidates as $candidate) {
            if (Storage::disk($disk)->exists($candidate)) {
                try {
                    $stream = Storage::disk($disk)->get($candidate);
                    $mime = 'image/jpeg';
                    if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $candidate)) {
                        $mime = 'image/' . (preg_match('/\.jpg|jpeg$/i', $candidate) ? 'jpeg' :
                                (preg_match('/\.png$/i', $candidate) ? 'png' : 'gif'));
                    }
                    return response($stream, 200)
                        ->header('Content-Type', $mime)
                        ->header('Content-Length', strlen($stream));
                } catch (\Throwable $e) {
                    \Log::warning("Error reading {$candidate} from {$disk}: " . $e->getMessage());
                }
            }
        }

        // File tidak ditemukan
        \Log::warning("bukti_pembayaran file not found: {$filename}");

        return response()->json([
            'error' => 'File not found',
            'message' => 'Bukti pembayaran tidak tersedia. Silahkan upload kembali.',
            'filename' => $filename,
        ], 404);
    }    public function verifikasi($id)
    {
        $peminjaman = Peminjaman::findOrFail($id);

        $peminjaman->update([
            'status_pembayaran' => 'terverifikasi',
            'status' => 'disetujui',
            'waktu_pembayaran' => $peminjaman->waktu_pembayaran ?? now()
        ]);

        return back()->with('success', 'Pembayaran telah diverifikasi dan peminjaman disetujui.');
    }

    /**
     * Serve bukti pembayaran stored as BLOB in the database.
     */
    public function showBuktiBlob($id)
    {
        $p = Peminjaman::findOrFail($id);
        $blob = $p->bukti_pembayaran_blob ?? null;
        if (!$blob) {
            return response()->json(['error' => 'File not found in database'], 404);
        }

        $mime = $p->bukti_pembayaran_mime ?? null;
        if (!$mime) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($blob) ?: 'application/octet-stream';
        }

        return response($blob, 200)
            ->header('Content-Type', $mime)
            ->header('Content-Length', strlen($blob));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ruang_id' => 'required',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'keperluan' => 'required',
            'bukti_pembayaran' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            'bukti_pembayaran.required' => 'Bukti pembayaran harus diunggah berupa file gambar (jpg, png)'
        ]);

        // Calculate booking duration and cost
        $mulai = strtotime($request->jam_mulai);
        $selesai = strtotime($request->jam_selesai);
        $durasi = ceil(($selesai - $mulai) / 3600); // Duration in hours
        $biaya = $durasi * 50000; // Rp. 50.000 per hour

        // Simpan file ke database BLOB
        $file = $request->file('bukti_pembayaran');
        $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());

        // ✅ PRIMARY: Baca file dan simpan ke BLOB
        try {
            $contents = file_get_contents($file->getRealPath());
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($contents) ?: 'image/jpeg';
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca file bukti pembayaran.');
        }

        // Create Peminjaman record dengan BLOB data
        try {
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
                'waktu_pembayaran' => now(),
                'bukti_pembayaran_blob' => $contents,
                'bukti_pembayaran_mime' => $mime,
                'bukti_pembayaran_name' => $filename,
                'bukti_pembayaran_size' => strlen($contents),
            ]);
        } catch (\Throwable $e) {
            \Log::error("Failed to create peminjaman with BLOB: " . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan peminjaman dengan bukti pembayaran.');
        }

        // SECONDARY: Simpan ke file storage sebagai backup (optional)
        try {
            $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
            $relativePath = $file->storeAs('bukti_pembayaran', $filename, $disk);

            // Jika local disk, juga copy ke public folder
            if ($disk === 'public') {
                $publicDir = public_path('bukti_pembayaran');
                if (!is_dir($publicDir)) {
                    mkdir($publicDir, 0755, true);
                }
                $storedFull = storage_path('app/public/' . $relativePath);
                $publicFull = $publicDir . DIRECTORY_SEPARATOR . $filename;
                if (file_exists($storedFull)) {
                    copy($storedFull, $publicFull);
                }
            }

            // Update bukti_pembayaran kolom sebagai reference
            $peminjaman->update(['bukti_pembayaran' => $relativePath]);
        } catch (\Throwable $e) {
            // Backup storage gagal, tidak masalah karena BLOB sudah tersimpan
            \Log::warning("Optional file storage backup failed: " . $e->getMessage());
        }

        return redirect()->route('home')->with('success', 'Pengajuan peminjaman berhasil dibuat!');
    }
}
