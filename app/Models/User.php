<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'email',
        'name',
        'username',
        'password',
        'role',
        'no_hp',
        'prioritas_level',
        'prioritas_since',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class, 'user_id');
    }

    public function getPrioritasDiscountPercentAttribute(): int
    {
        $discounts = config('prioritas.discounts', [0 => 0, 1 => 5, 2 => 15, 3 => 25]);
        $level = (int)($this->prioritas_level ?? 0);
        return (int)($discounts[$level] ?? 0);
    }

    public function recalculatePrioritas(): void
    {
        $stats = $this->peminjaman()
            ->where('status', 'disetujui')
            ->whereIn('status_pembayaran', ['terverifikasi', 'lunas'])
            ->select(DB::raw('COUNT(*) as cnt'), DB::raw('COALESCE(SUM(biaya),0) as total'))
            ->first();

        $cnt = (int)($stats->cnt ?? 0);
        $total = (int)round($stats->total ?? 0);

        $tiers = config('prioritas.tiers', []);
        $newLevel = 0;
        foreach ($tiers as $tier) {
            if ($cnt >= (int)$tier['min_count'] && $total >= (int)$tier['min_total']) {
                $newLevel = max($newLevel, (int)$tier['level']);
            }
        }

        $oldLevel = (int)($this->prioritas_level ?? 0);
        if ($newLevel !== $oldLevel) {
            $this->prioritas_level = $newLevel;
            if ($oldLevel === 0 && $newLevel > 0 && empty($this->prioritas_since)) {
                $this->prioritas_since = now();
            }
            $this->save();
        }
    }
}
