<?php

namespace App\Models;

use Database\Factories\KelasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    /** @use HasFactory<KelasFactory> */
    use HasFactory;

    protected $table = 'kelas';

    protected $fillable = ['nama_kelas', 'tingkat', 'jurusan', 'tahun_pelajaran_id', 'wali_kelas'];

    public function tahunPelajaran(): BelongsTo
    {
        return $this->belongsTo(TahunPelajaran::class, 'tahun_pelajaran_id');
    }

    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }

    /**
     * Human label such as "VII-A · 2026/2027 ganjil".
     */
    public function label(): string
    {
        $year = $this->tahunPelajaran;

        return $year ? "{$this->nama_kelas} · {$year->tahun} {$year->semester}" : $this->nama_kelas;
    }
}
