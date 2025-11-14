<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ruang;

class RuangSeeder extends Seeder
{
    public function run(): void
    {
        Ruang::create([
            'nama_ruang' => 'Ruang Bioskop',
            'deskripsi' => 'Ruang untuk nonton bareng',
        ]);
    }
}
