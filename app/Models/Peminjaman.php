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
     * Accessor untuk mendapatkan URL gambar bukti pembayaran
     * ✅ PRIMARY: Gunakan BLOB dari database (guaranteed work di Railway)
     *
     * Langsung mengambil dari BLOB by ID:
     * /pembayaran/bukti/blob/{id} → Database query → Binary image
     *
     * Usage: $peminjaman->bukti_pembayaran_src
     */
    public function getBuktiPembayaranSrcAttribute()
    {
        // ✅ PRIMARY: Jika BLOB tersedia, serve langsung dari database by ID
        if (!empty($this->attributes['bukti_pembayaran_blob'])) {
            // Gunakan ID untuk query yang paling cepat dan reliable
            return route('pembayaran.bukti.blob', ['id' => $this->id]);
        }

        // FALLBACK: Jika BLOB kosong, coba gunakan file path
        $value = $this->attributes['bukti_pembayaran'] ?? null;
        if (!$value) {
            return null;
        }

        // Jika sudah full URL, return as-is
        if (is_string($value) && preg_match('/^https?:\/\//', $value)) {
            return $value;
        }

        // Normalize path
        $storageKey = $value;
        if (strpos($storageKey, 'public/') === 0) {
            $storageKey = substr($storageKey, 7);
        }

        // Generate route URL (fallback)
        return route('pembayaran.bukti', ['filename' => basename($storageKey)]);
    }
}
