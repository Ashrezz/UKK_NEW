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
            // Hapus file lama jika ada (hapus di storage dan juga di public copy)
            if ($peminjaman->bukti_pembayaran && strpos($peminjaman->bukti_pembayaran, 'bukti_pembayaran/') === 0) {
                try {
                    $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
                    Storage::disk($disk)->delete($peminjaman->bukti_pembayaran);
                } catch (\Throwable $e) {
                    // ignore deletion errors
                }
                // Remove public copy if exists
                try {
                    $oldBasename = basename($peminjaman->bukti_pembayaran);
                    $publicPathOld = public_path('bukti_pembayaran/' . $oldBasename);
                    if (file_exists($publicPathOld)) {
                        @unlink($publicPathOld);
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            // Simpan file baru ke configured disk (local/public atau s3)
            $file = $request->file('bukti_pembayaran');
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
            $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
            $relativePath = $file->storeAs('bukti_pembayaran', $filename, $disk);

            // If we're using local/public disk, also copy a public-accessible copy to public/bukti_pembayaran
            if ($disk === 'public') {
                try {
                    $publicDir = public_path('bukti_pembayaran');
                    if (!is_dir($publicDir)) {
                        mkdir($publicDir, 0755, true);
                    }
                    $storedFull = storage_path('app/public/' . $relativePath);
                    $publicFull = $publicDir . DIRECTORY_SEPARATOR . $filename;
                    if (file_exists($storedFull)) {
                        copy($storedFull, $publicFull);
                    } else {
                        // If storeAs returned path but file not yet moved, move the uploaded file directly
                        $file->move($publicDir, $filename);
                    }
                } catch (\Throwable $e) {
                    // ignore copy errors - storage still contains the file
                }
            }

            // Update database dengan path relative
            // also save blob into DB (temporary persistence for UKK)
            try {
                $contents = file_get_contents($file->getRealPath());
                $peminjaman->bukti_pembayaran_blob = $contents;
                $peminjaman->bukti_pembayaran_mime = $file->getClientMimeType() ?? 'image/jpeg';
                $peminjaman->bukti_pembayaran_name = $filename;
                $peminjaman->bukti_pembayaran_size = filesize($file->getRealPath()) ?: strlen($contents);
            } catch (\Throwable $e) {
                // ignore DB blob write errors
            }

            $peminjaman->update([
                'bukti_pembayaran' => $relativePath,
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
     * Serve a bukti pembayaran file from the configured storage disk.
     * Supports both local (public) and S3 storage.
     */
    public function showBukti($filename)
    {
        // sanitize filename to avoid traversal
        $filename = basename($filename);

        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';

        $candidates = [
            $filename,
            'bukti_pembayaran/' . $filename,
        ];

        foreach ($candidates as $candidate) {
            if (Storage::disk($disk)->exists($candidate)) {
                // For local/public disk, serve file directly
                if ($disk === 'public') {
                    $full = storage_path('app/public/' . $candidate);
                    if (file_exists($full)) {
                        return response()->file($full);
                    }
                }

                // Stream via Storage API (works for both S3 and local)
                try {
                    $stream = Storage::disk($disk)->get($candidate);
                    $mime = 'application/octet-stream';
                    // Guess MIME type from extension
                    if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $candidate)) {
                        $mime = 'image/' . (preg_match('/\.jpg$/i', $candidate) ? 'jpeg' :
                                (preg_match('/\.png$/i', $candidate) ? 'png' : 'gif'));
                    }
                    return response($stream, 200, ['Content-Type' => $mime]);
                } catch (\Throwable $e) {
                    \Log::warning("Error reading {$candidate} from {$disk}: " . $e->getMessage());
                    // continue to next candidate
                }
            }
        }

        // File not found - return helpful error message
        \Log::warning("bukti_pembayaran file not found: {$filename} (disk: {$disk})");

        return response()->json([
            'error' => 'File not found',
            'message' => 'Bukti pembayaran file tidak tersedia atau telah dihapus.',
            'filename' => $filename,
            'disk' => $disk,
            'note' => $disk === 's3'
                ? 'File tidak ditemukan di AWS S3 storage.'
                : 'File tidak ditemukan di local storage. Jika menggunakan Railway, file mungkin tidak persisten. Silahkan upload kembali.'
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
            'bukti_pembayaran' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ], [
            'bukti_pembayaran.required' => 'Bukti pembayaran harus diunggah berupa file gambar (jpg, png)'
        ]);

        // Calculate booking duration and cost
        $mulai = strtotime($request->jam_mulai);
        $selesai = strtotime($request->jam_selesai);
        $durasi = ceil(($selesai - $mulai) / 3600); // Duration in hours
        $biaya = $durasi * 50000; // Rp. 50.000 per hour

        // Simpan file ke configured disk (local/public atau s3)
        $file = $request->file('bukti_pembayaran');
        $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        $relativePath = $file->storeAs('bukti_pembayaran', $filename, $disk);

        // If local public disk is used, also keep a copy under public/bukti_pembayaran for direct serving
        if ($disk === 'public') {
            try {
                $publicDir = public_path('bukti_pembayaran');
                if (!is_dir($publicDir)) {
                    mkdir($publicDir, 0755, true);
                }
                $storedFull = storage_path('app/public/' . $relativePath);
                $publicFull = $publicDir . DIRECTORY_SEPARATOR . $filename;
                if (file_exists($storedFull)) {
                    copy($storedFull, $publicFull);
                } else {
                    $file->move($publicDir, $filename);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

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

        // Save blob data into the DB for temporary persistence (UKK)
        try {
            $contents = file_get_contents($file->getRealPath());
            $peminjaman->bukti_pembayaran_blob = $contents;
            $peminjaman->bukti_pembayaran_mime = $file->getClientMimeType() ?? 'image/jpeg';
            $peminjaman->bukti_pembayaran_name = $filename;
            $peminjaman->bukti_pembayaran_size = filesize($file->getRealPath()) ?: strlen($contents);
            $peminjaman->save();
        } catch (\Throwable $e) {
            // ignore if DB cannot store blob
        }

        return redirect()->route('home')->with('success', 'Pengajuan peminjaman berhasil dibuat!');
    }
}
