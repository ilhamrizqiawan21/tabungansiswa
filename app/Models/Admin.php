<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class Admin extends Authenticatable
{
    use Notifiable;
    protected $table = 'admin';
    protected $fillable = ['username', 'password', 'nama', 'role'];
    protected $hidden = ['password', 'remember_token'];
    public function transaksiDiminta() { return $this->hasMany(TransaksiApproval::class, 'requested_by'); }
    public function transaksiDisetujui() { return $this->hasMany(TransaksiApproval::class, 'approved_by'); }
    public function auditLogs() { return $this->hasMany(AuditLog::class); }
    public function isAdmin(): bool { return $this->role === 'admin'; }
}
