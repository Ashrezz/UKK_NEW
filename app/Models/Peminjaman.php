<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Peminjaman extends Model
{
    use SoftDeletes;
    protected $table = 'peminjaman';

    protected $fillable = [
        'user_id',
        'ruang_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'keperluan',
        'status',
        'biaya',
        'bukti_pembayaran',
    'bukti_pembayaran_blob',
    'bukti_pembayaran_mime',
    'bukti_pembayaran_name',
    'bukti_pembayaran_size',
        'status_pembayaran',
        'waktu_pembayaran',
        'alasan_penolakan',
        'dibatalkan_oleh',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ruang()
    {
        return $this->belongsTo(Ruang::class, 'ruang_id');
    }

    /**
     * Accessor to get a usable image src for bukti_pembayaran.
     * - If the column contains raw binary image data (BLOB), it will return a data URI.
     * - If the column contains a file path or URL, it will return a storage URL or the URL as-is.
     * - Returns null when no image available.
     * - Supports both local (public) disk and S3.
     *
     * Usage: $peminjaman->bukti_pembayaran_src
     */
    public function getBuktiPembayaranSrcAttribute()
    {
        // If a BLOB exists, prefer the blob-serving route
        if (!empty($this->attributes['bukti_pembayaran_blob'])) {
            return route('pembayaran.bukti.blob', ['id' => $this->id]);
        }

        $value = $this->attributes['bukti_pembayaran'] ?? null;
        if (!$value) {
            return null;
        }

        // If it's already a full URL, return as-is
        if (is_string($value) && preg_match('/^https?:\/\//', $value)) {
            return $value;
        }

        // Normalize values that might include the public/ prefix
        $storageKey = $value;
        if (strpos($storageKey, 'public/') === 0) {
            $storageKey = substr($storageKey, 7);
        }

        // Detect which disk is configured
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';

        // Try a few possibilities:
        // 1) the value as-is (e.g. 'bukti_pembayaran/..' or 'somefile.png')
        // 2) prefixed with bukti_pembayaran/ when value is just a filename
        $candidates = [$storageKey];
        if (basename($storageKey) === $storageKey) {
            $candidates[] = 'bukti_pembayaran/' . $storageKey;
        }

        try {
            foreach ($candidates as $candidate) {
                // If a publicly accessible copy exists under public/bukti_pembayaran, return that URL
                $publicCandidate = public_path('bukti_pembayaran/' . basename($candidate));
                if (file_exists($publicCandidate)) {
                    return asset('bukti_pembayaran/' . basename($candidate));
                }

                if (Storage::disk($disk)->exists($candidate)) {
                    // Serve via controller route so it works for both local and S3
                    return route('pembayaran.bukti', ['filename' => basename($candidate)]);
                }
            }
        } catch (\Throwable $e) {
            // ignore and fallback
        }

        // Fallback: assume it's a path relative to storage/app/public or S3
        if ($disk === 's3') {
            // For S3, return a path that the route will handle
            return route('pembayaran.bukti', ['filename' => basename($storageKey)]);
        }

        return asset('storage/' . ltrim($storageKey, '/'));
    }
}
