<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiApproval extends Model
{
    protected $table = 'transaksi_approval';

    public $timestamps = false;

    protected $fillable = [
        'siswa_id',
        'tanggal',
        'jumlah',
        'keterangan',
        'transaksi_id',
        'status_id',
        'requested_by',
        'approved_by',
        'rejection_reason',
        'request_date',
        'approval_date',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
        'request_date' => 'datetime',
        'approval_date' => 'datetime',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    public function status()
    {
        return $this->belongsTo(ApprovalStatus::class, 'status_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(Admin::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }
}
