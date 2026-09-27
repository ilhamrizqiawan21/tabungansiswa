<?php

namespace App\Models;

use Database\Factories\SiswaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Siswa extends Model
{
    /** @use HasFactory<SiswaFactory> */
    use HasFactory, SoftDeletes;

    public const STATUSES = ['aktif', 'lulus', 'keluar'];

    protected $table = 'siswa';

    protected $fillable = ['nis', 'nama', 'kelas_id', 'kontak', 'status'];

    protected $attributes = ['status' => 'aktif'];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'siswa_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Current effective balance (pending withdrawals excluded).
     */
    public function currentBalance(): float
    {
        return (float) $this->transaksi()
            ->effective()
            ->selectRaw(Transaksi::SALDO_EXPRESSION.' as saldo')
            ->value('saldo');
    }

    public function getSaldoAttribute(): string
    {
        return number_format($this->currentBalance(), 2, '.', '');
    }
}
