<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Kelas extends Model
{
    protected $table = 'kelas';
    protected $fillable = ['nama_kelas', 'tingkat', 'jurusan', 'tahun_pelajaran_id', 'wali_kelas'];
    public function tahunPelajaran() { return $this->belongsTo(TahunPelajaran::class, 'tahun_pelajaran_id'); }
    public function siswa() { return $this->hasMany(Siswa::class, 'kelas_id'); }
}
