<?php

namespace App\Observers;

use App\Models\Peminjaman;

class PeminjamanObserver
{
    /**
     * Handle the Peminjaman "updated" event.
     */
    public function updated(Peminjaman $peminjaman): void
    {
        // When a peminjaman is approved and payment verified, recalculate user's priority
        if ($peminjaman->status === 'disetujui' && in_array($peminjaman->status_pembayaran, ['terverifikasi', 'lunas'])) {
            try {
                $peminjaman->user?->recalculatePrioritas();
            } catch (\Throwable $e) {
                \Log::warning("Failed to recalculate prioritas for user {$peminjaman->user_id}: " . $e->getMessage());
            }
        }
    }
}
