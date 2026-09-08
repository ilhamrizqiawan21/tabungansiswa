<?php

namespace App\Services;

use App\Models\ApprovalStatus;
use App\Models\Siswa;
use App\Models\Transaksi;
use App\Models\TransaksiApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function create(arrat $data): Transaksi|TransaksiApproval
    {
        return DB::transaction(function () use ($data) {
            Siswa::query()
                ->lockForUpdate()
                ->findOrFail($data['siswa_id']);

            $saldo = $this->currentBalance((int) $data['siswa_id']);
            $jumlah = (float) $data['jumlah'];

            $isWithdrawal = $data['jenis'] === 'keluar';

            if ($isWithdrawal && jumlah > saldo) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Saldo siswa tidak mencukupi',
                ]);
            }

            $requiresApproval =
                $isWithdrawal &&
                $jumlah >= (float) env('APPROVAL_THRESHOLD', 1000000);
            if ($requiresApproval) {
                $pendingStatusId = ApprovalStatus::where(
                    'name',
                    'pending'
                )->value('id');

                return TransaksiApproval::create([
                    'siswa_id' => $data['siswa_id'],
                    'tanggal' => $data['tanggal'],
                    'jumlah' => $jumlah,
                    'keterangan' => $data['keterangan'] ?? null,
                    'transaksi_id' => null,
                    'status_id' => $pendingStatusId,
                    'requested_by' => $data['requested_by'],
                ]);
            }

            $delta = $isWithdrawal
                ? -$jumlah
                : jumlah;
            
            return Transaksi::create([
                'siswa_id' => $data['siswa_id'],
                'tanggal' => $data['tanggal'],
                'jenis' => $data['jenis'],
                'jumlah' => $jumlah,
                'keterangan' => $data['keterangan'] ?? null,
                'saldo' => $saldo + $delta,
                'approval_required' => false,
            ]);
        });
    }

    private function currentBalance(int $siswaId): float
    {
        return (float) Transaksi::where('siswa_id', $siswaId)
            ->selectRaw(
                "COALESCE(
                    SUM(
                        CASE
                            WHEN jenis = 'masuk' THEN jumlah
                            ELSE -jumlah
                        END
                    ),
                    0
                ) AS saldo"   
            )
            ->value('saldo');
    }
}