<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $table = 'siswa';

    protected $fillable = ['nis', 'nama', 'kelas_id', 'kontak'];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'siswa_id');
    }

    public function getSaldoAttribute(): string
    {
        return number_format(
            (float) $this->transaksi()
                ->selectRaw(
                    "COALESCE(
                    SUM(
                        CASE 
                            WHEN jenis = 'masuk' THEN jumlah
                            ELSE -jumlah
                        END
                    ),
                    0
                    ) as saldo"
                )
                ->value('saldo'),
            2,
            '.',
            ''
        );
    }
}
