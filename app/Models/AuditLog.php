<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model
{
    protected $table = 'audit_log';
    public $timestamps = false;
    protected $fillable = ['admin_id', 'table_name', 'record_id', 'action', 'old_values', 'new_values', 'description', 'ip_address', 'user_agent', 'created_at'];
    protected $casts = ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];
    public function admin() { return $this->belongsTo(Admin::class); }
}
