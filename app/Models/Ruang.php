<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ruang extends Model
{
    protected $table = 'ruang';

    protected $fillable = [
        'nama_ruang',
        'kapasitas',
        'deskripsi'
    ];

    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class, 'ruang_id');
    }
}
