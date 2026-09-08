<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Transaksi extends Model
{
    protected $table = 'transaksi';
    protected $fillable = ['siswa_id', 'tanggal', 'jenis', 'jumlah', 'keterangan', 'saldo', 'approval_required'];
    protected $casts = ['tanggal' => 'date', 'jumlah' => 'decimal:2', 'saldo' => 'decimal:2', 'approval_required' => 'boolean'];
    public function siswa() { return $this->belongsTo(Siswa::class, 'siswa_id'); }
    public function approval() { return $this->hasOne(TransaksiApproval::class, 'transaksi_id'); }
    public function scopeEffective($query) {
        return $query->where(function ($query) {
            $query
                ->whereDoesntHave('approval')
                ->orWhereHas('approval.status', function ($status) {
                    $status->where('name', 'approved');
                });
        });
    }
    public function scopeSetoran($query) { return $query->where('jenis', 'masuk'); }
    public function scopePenarikan($query) { return $query->where('jenis', 'keluar'); }
}
