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

                \Log::info("✓ Saved to BLOB: {$filename} (" . round(strlen($contents) / 1024, 2) . "KB)");
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

                // Update bukti_pembayaran path reference
                $peminjaman->bukti_pembayaran = $relativePath;
            } catch (\Throwable $e) {
                // File storage backup gagal, tapi tidak masalah karena BLOB sudah tersimpan
                \Log::warning("Optional file storage backup failed: " . $e->getMessage());
            }

            // Update status pembayaran dan SAVE semuanya
            $peminjaman->status_pembayaran = 'menunggu_verifikasi';
            $peminjaman->waktu_pembayaran = now();
            $peminjaman->save();

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

        // Try to find a peminjaman record that references this filename
        $peminjaman = Peminjaman::where('bukti_pembayaran_name', $filename)
            ->orWhere('bukti_pembayaran', 'like', '%' . $filename)
            ->orWhere('id', preg_replace('/[^0-9]/', '', $filename))
            ->first();

        // If we have a record and it already has a BLOB, serve it
        if ($peminjaman && !empty($peminjaman->bukti_pembayaran_blob)) {
            $blob = $peminjaman->bukti_pembayaran_blob;
            $mime = $peminjaman->bukti_pembayaran_mime ?? 'image/jpeg';

            return response($blob, 200)
                ->header('Content-Type', $mime)
                ->header('Content-Length', strlen($blob))
                ->header('Cache-Control', 'public, max-age=3600');
        }

        // If we have a record but no BLOB, attempt to fetch the original file (storage or URL)
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        $candidates = [];

        if ($peminjaman) {
            $orig = $peminjaman->bukti_pembayaran;
            if ($orig) {
                // If it's a full URL, try to fetch it directly
                if (is_string($orig) && preg_match('/^https?:\/\//', $orig)) {
                    $candidates[] = $orig;
                } else {
                    // normalize
                    $path = $orig;
                    if (strpos($path, 'public/') === 0) {
                        $path = substr($path, 7);
                    }
                    $candidates[] = $path;
                    $candidates[] = 'bukti_pembayaran/' . basename($path);
                    $candidates[] = basename($path);
                }
            } else {
                // fallback to filename candidates
                $candidates[] = $filename;
                $candidates[] = 'bukti_pembayaran/' . $filename;
            }
        } else {
            // No DB record - try generic storage locations
            $candidates[] = $filename;
            $candidates[] = 'bukti_pembayaran/' . $filename;
        }

        // Try disks first (public or s3)
        foreach ($candidates as $candidate) {
            try {
                // If candidate looks like a URL, fetch via HTTP
                if (preg_match('/^https?:\/\//', $candidate)) {
                    try {
                        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                        $contents = @file_get_contents($candidate, false, $ctx);
                        if ($contents !== false) {
                            $finfo = new \finfo(FILEINFO_MIME_TYPE);
                            $mime = $finfo->buffer($contents) ?: 'image/jpeg';
                            // Save into DB if we have a record
                            if ($peminjaman) {
                                $peminjaman->bukti_pembayaran_blob = $contents;
                                $peminjaman->bukti_pembayaran_mime = $mime;
                                $peminjaman->bukti_pembayaran_name = basename($candidate);
                                $peminjaman->bukti_pembayaran_size = strlen($contents);
                                $peminjaman->save();
                            }

                            return response($contents, 200)
                                ->header('Content-Type', $mime)
                                ->header('Content-Length', strlen($contents));
                        }
                    } catch (\Throwable $e) {
                        \Log::warning("HTTP fetch failed for {$candidate}: " . $e->getMessage());
                    }
                    continue;
                }

                // Try storage disk
                if (Storage::disk($disk)->exists($candidate)) {
                    $stream = Storage::disk($disk)->get($candidate);
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->buffer($stream) ?: 'image/jpeg';

                    // Save into DB if we have a record
                    if ($peminjaman) {
                        $peminjaman->bukti_pembayaran_blob = $stream;
                        $peminjaman->bukti_pembayaran_mime = $mime;
                        $peminjaman->bukti_pembayaran_name = basename($candidate);
                        $peminjaman->bukti_pembayaran_size = strlen($stream);
                        $peminjaman->save();
                    }

                    return response($stream, 200)
                        ->header('Content-Type', $mime)
                        ->header('Content-Length', strlen($stream));
                }
            } catch (\Throwable $e) {
                // ignore and continue
                \Log::warning("Error attempting to fetch candidate {$candidate}: " . $e->getMessage());
            }
        }

        // Also try common file locations on disk
        $localPaths = [
            storage_path('app/public/bukti_pembayaran/' . $filename),
            public_path('bukti_pembayaran/' . $filename),
        ];

        foreach ($localPaths as $local) {
            if (file_exists($local)) {
                $contents = file_get_contents($local);
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->buffer($contents) ?: 'image/jpeg';

                if ($peminjaman) {
                    $peminjaman->bukti_pembayaran_blob = $contents;
                    $peminjaman->bukti_pembayaran_mime = $mime;
                    $peminjaman->bukti_pembayaran_name = basename($local);
                    $peminjaman->bukti_pembayaran_size = strlen($contents);
                    $peminjaman->save();
                }

                return response($contents, 200)
                    ->header('Content-Type', $mime)
                    ->header('Content-Length', strlen($contents));
            }
        }

        // Not found anywhere
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
     * Direct by ID - simplest and most reliable method
     */
    public function showBuktiBlob($id)
    {
        try {
            $p = Peminjaman::findOrFail($id);
            $blob = $p->bukti_pembayaran_blob ?? null;

            if (!$blob || empty($blob)) {
                \Log::warning("BLOB not found for peminjaman {$id}");
                return response()->json(['error' => 'Bukti pembayaran tidak tersedia'], 404);
            }

            // Detect MIME type
            $mime = $p->bukti_pembayaran_mime ?? null;
            if (!$mime) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->buffer($blob) ?: 'image/jpeg';
            }

            // Log for debugging
            \Log::info('Serving BLOB for peminjaman ' . $id . ': size=' . strlen($blob) . ' bytes, mime=' . $mime);

            // Return binary image with proper headers
            return response($blob, 200)
                ->header('Content-Type', $mime)
                ->header('Content-Disposition', 'inline')
                ->header('Content-Length', strlen($blob))
                ->header('Cache-Control', 'public, max-age=86400');

        } catch (\Throwable $e) {
            \Log::error("Error serving BLOB for peminjaman {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil bukti pembayaran'], 500);
        }
    }

    /**
     * Debug endpoint: show BLOB metadata for a record (JSON)
     * Usage: /pembayaran/debug/blob/{id}
     */
    public function debugBlob($id)
    {
        $p = Peminjaman::withTrashed()->find($id);
        
        if (!$p) {
            return response()->json(['error' => 'Record not found', 'id' => $id], 404);
        }

        $blobSize = $p->bukti_pembayaran_blob ? strlen($p->bukti_pembayaran_blob) : 0;
        $blobPresent = !empty($p->bukti_pembayaran_blob);

        return response()->json([
            'id' => $p->id,
            'deleted_at' => $p->deleted_at,
            'bukti_pembayaran' => $p->bukti_pembayaran,
            'bukti_pembayaran_mime' => $p->bukti_pembayaran_mime,
            'bukti_pembayaran_name' => $p->bukti_pembayaran_name,
            'bukti_pembayaran_size' => $p->bukti_pembayaran_size,
            'blob_present' => $blobPresent,
            'blob_actual_size' => $blobSize,
            'accessor_url' => $p->bukti_pembayaran_src,
        ]);
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

    /**
     * Emergency endpoint: Populate missing BLOBs with placeholder images
     * Can be called via GET /pembayaran/populate-missing-blobs
     * Returns JSON with results
     */
    public function populateMissingBlobs()
    {
        $records = Peminjaman::whereNotNull('bukti_pembayaran')
            ->where(function ($query) {
                $query->whereNull('bukti_pembayaran_blob')
                    ->orWhere('bukti_pembayaran_blob', '');
            })
            ->get();

        $count = $records->count();

        if ($count === 0) {
            return response()->json([
                'status' => 'success',
                'message' => 'All records already have BLOB data',
                'generated' => 0,
                'failed' => 0,
            ]);
        }

        $generated = 0;
        $failed = 0;
        $errors = [];

        foreach ($records as $peminjaman) {
            try {
                $placeholder = $this->generatePlaceholderImage();
                $filename = basename($peminjaman->bukti_pembayaran ?? 'bukti.png');

                $peminjaman->bukti_pembayaran_blob = $placeholder;
                $peminjaman->bukti_pembayaran_mime = 'image/png';
                $peminjaman->bukti_pembayaran_name = $filename;
                $peminjaman->bukti_pembayaran_size = strlen($placeholder);
                $peminjaman->save();

                $generated++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "ID {$peminjaman->id}: " . $e->getMessage();
            }
        }

        return response()->json([
            'status' => $failed === 0 ? 'success' : 'partial',
            'message' => "Processed {$count} records",
            'generated' => $generated,
            'failed' => $failed,
            'errors' => $errors,
        ]);
    }

    /**
     * Generate a simple placeholder image (200x200 PNG with text)
     */
    private function generatePlaceholderImage()
    {
        $image = imagecreatetruecolor(200, 200);

        $bgColor = imagecolorallocate($image, 200, 200, 200);
        $textColor = imagecolorallocate($image, 100, 100, 100);
        $borderColor = imagecolorallocate($image, 150, 150, 150);

        imagefilledrectangle($image, 0, 0, 200, 200, $bgColor);
        imagerectangle($image, 0, 0, 199, 199, $borderColor);

        imagestring($image, 2, 50, 90, "No Image", $textColor);
        imagestring($image, 2, 55, 105, "Provided", $textColor);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return $imageData;
    }
}
