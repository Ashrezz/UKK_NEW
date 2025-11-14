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
     *
     * Usage: $peminjaman->bukti_pembayaran_src
     */
    public function getBuktiPembayaranSrcAttribute()
    {
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

        // Try a few possibilities on the public disk:
        // 1) the value as-is (e.g. 'bukti_pembayaran/..' or 'somefile.png')
        // 2) prefixed with bukti_pembayaran/ when value is just a filename
        $candidates = [$storageKey];
        if (basename($storageKey) === $storageKey) {
            $candidates[] = 'bukti_pembayaran/' . $storageKey;
        }

        try {
                foreach ($candidates as $candidate) {
                if (Storage::disk('public')->exists($candidate)) {
                    // Serve via controller route so it works even if public/storage symlink isn't present
                    return route('pembayaran.bukti', ['filename' => basename($candidate)]);
                }
            }
        } catch (\Throwable $e) {
            // ignore and fallback
        }

        // Fallback: assume it's a path relative to storage/app/public
        return asset('storage/' . ltrim($storageKey, '/'));
    }
}
