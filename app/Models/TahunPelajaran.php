<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TahunPelajaran extends Model
{
    protected $table = 'tahun_pelajaran';
    protected $fillable = ['tahun', 'semester', 'status'];
    public function kelas() { return $this->hasMany(Kelas::class, 'tahun_pelajaran_id'); }
    public function scopeAktif($query) { return $query->where('status', 'aktif'); }
}
