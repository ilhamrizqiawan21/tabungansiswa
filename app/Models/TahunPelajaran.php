<?php

namespace App\Models;

use Database\Factories\TahunPelajaranFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunPelajaran extends Model
{
    /** @use HasFactory<TahunPelajaranFactory> */
    use HasFactory;

    protected $table = 'tahun_pelajaran';

    protected $fillable = ['tahun', 'semester', 'status'];

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'tahun_pelajaran_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }
}
