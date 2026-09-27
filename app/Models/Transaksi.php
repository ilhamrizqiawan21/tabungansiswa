<?php

namespace App\Models;

use Database\Factories\TransaksiFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaksi extends Model
{
    /** @use HasFactory<TransaksiFactory> */
    use HasFactory, SoftDeletes;

    /** Signed running-balance delta: masuk adds, keluar subtracts. */
    public const SALDO_EXPRESSION = "COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END), 0)";

    /** Separate masuk/keluar totals (not netted), aliased as `masuk` and `keluar`. */
    public const RINGKASAN_EXPRESSION = "COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE 0 END), 0) as masuk, COALESCE(SUM(CASE WHEN jenis = 'keluar' THEN jumlah ELSE 0 END), 0) as keluar";

    protected $table = 'transaksi';

    protected $fillable = ['siswa_id', 'tanggal', 'jenis', 'jumlah', 'keterangan', 'saldo', 'approval_required', 'reversal_of_id'];

    protected $casts = ['tanggal' => 'date', 'jumlah' => 'decimal:2', 'saldo' => 'decimal:2', 'approval_required' => 'boolean'];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id')->withTrashed();
    }

    public function approval(): HasOne
    {
        return $this->hasOne(TransaksiApproval::class, 'transaksi_id');
    }

    /** The transaction this row corrects, when it is a reversal. */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'reversal_of_id');
    }

    /** The reversal that cancelled this transaction, if any. */
    public function reversal(): HasOne
    {
        return $this->hasOne(Transaksi::class, 'reversal_of_id');
    }

    public function isReversal(): bool
    {
        return $this->reversal_of_id !== null;
    }

    public function scopeEffective(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query
                ->whereDoesntHave('approval')
                ->orWhereHas('approval.status', function ($status) {
                    $status->where('name', 'approved');
                });
        });
    }

    public function scopeSetoran(Builder $query): Builder
    {
        return $query->where('jenis', 'masuk');
    }

    public function scopePenarikan(Builder $query): Builder
    {
        return $query->where('jenis', 'keluar');
    }
}
