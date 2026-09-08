<?php
namespace App\Services;
use App\Models\Siswa;
use App\Models\Transaksi;
use App\Models\ApprovalStatus;
use App\Models\TransaksiApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class TransactionService
{
    public function create(array $data): Transaksi
    {
        return DB::transaction(function () use ($data) {
            Siswa::query()->lockForUpdate()->findOrFail($data['siswa_id']);
            $saldo = (float) Transaksi::where('siswa_id', $data['siswa_id'])->latest('id')->value('saldo');
            $delta = $data['jenis'] === 'masuk' ? (float) $data['jumlah'] : -(float) $data['jumlah'];
            if ($saldo + $delta < 0) throw ValidationException::withMessages(['jumlah' => 'Saldo siswa tidak mencukupi.']);
            $requiresApproval = $data['jenis'] === 'keluar' && (float) $data['jumlah'] >= (float) env('APPROVAL_THRESHOLD', 1000000);
            $transaction = Transaksi::create([...$data, 'saldo' => $saldo + $delta, 'approval_required' => $requiresApproval]);
            if ($requiresApproval) {
                TransaksiApproval::create(['transaksi_id' => $transaction->id, 'status_id' => ApprovalStatus::where('name', 'pending')->value('id'), 'requested_by' => $data['requested_by']]);
            }
            return $transaction;
        });
    }
}
