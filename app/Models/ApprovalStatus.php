<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ApprovalStatus extends Model
{
    protected $table = 'approval_status';
    protected $fillable = ['name', 'description'];
    public function approvals() { return $this->hasMany(TransaksiApproval::class, 'status_id'); }
}
