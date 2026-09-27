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
    /**
     * @param  array{siswa_id: int|string, tanggal: string, jenis: string, jumlah: int|float|string, keterangan?: string|null, requested_by: int}  $data
     */
    public function create(array $data): Transaksi|TransaksiApproval
    {
        return DB::transaction(function () use ($data) {
            $siswa = Siswa::query()
                ->lockForUpdate()
                ->findOrFail($data['siswa_id']);

            if ($siswa->status !== 'aktif') {
                throw ValidationException::withMessages([
                    'siswa_id' => 'Siswa sudah tidak aktif; transaksi baru tidak dapat dicatat.',
                ]);
            }

            $saldo = $this->currentBalance($siswa->id);
            $jumlah = (float) $data['jumlah'];

            $isWithdrawal = $data['jenis'] === 'keluar';

            if ($isWithdrawal && $jumlah > $saldo) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Saldo siswa tidak mencukupi',
                ]);
            }

            $requiresApproval =
                $isWithdrawal &&
                $jumlah >= (float) config('tabungan.approval_threshold');
            if ($requiresApproval) {
                $pendingStatusId = ApprovalStatus::where(
                    'name',
                    'pending'
                )->value('id');

                return TransaksiApproval::create([
                    'siswa_id' => $siswa->id,
                    'tanggal' => $data['tanggal'],
                    'jumlah' => $jumlah,
                    'keterangan' => $data['keterangan'] ?? null,
                    'transaksi_id' => null,
                    'status_id' => $pendingStatusId,
                    'requested_by' => $data['requested_by'],
                ]);
            }

            $delta = $isWithdrawal ? -$jumlah : $jumlah;

            return Transaksi::create([
                'siswa_id' => $siswa->id,
                'tanggal' => $data['tanggal'],
                'jenis' => $data['jenis'],
                'jumlah' => $jumlah,
                'keterangan' => $data['keterangan'] ?? null,
                'saldo' => $saldo + $delta,
                'approval_required' => false,
            ]);
        });
    }

    /**
     * Cancel a transaction by booking an opposite entry, leaving the original row
     * untouched so every running-balance snapshot stays valid.
     */
    public function reverse(Transaksi $original, string $alasan): Transaksi
    {
        return DB::transaction(function () use ($original, $alasan) {
            Siswa::withTrashed()->lockForUpdate()->findOrFail($original->siswa_id);

            $original = Transaksi::query()->lockForUpdate()->findOrFail($original->id);

            if ($original->isReversal()) {
                throw ValidationException::withMessages(['alasan' => 'Transaksi koreksi tidak dapat dikoreksi lagi.']);
            }

            if ($original->reversal()->exists()) {
                throw ValidationException::withMessages(['alasan' => 'Transaksi ini sudah pernah dikoreksi.']);
            }

            if (! Transaksi::effective()->whereKey($original->id)->exists()) {
                throw ValidationException::withMessages(['alasan' => 'Transaksi yang belum disetujui tidak dapat dikoreksi.']);
            }

            $saldo = $this->currentBalance($original->siswa_id);
            $jumlah = (float) $original->jumlah;
            $jenis = $original->jenis === 'masuk' ? 'keluar' : 'masuk';

            if ($jenis === 'keluar' && $jumlah > $saldo) {
                throw ValidationException::withMessages([
                    'alasan' => 'Saldo siswa saat ini tidak cukup untuk membatalkan setoran ini.',
                ]);
            }

            return Transaksi::create([
                'siswa_id' => $original->siswa_id,
                'tanggal' => today(),
                'jenis' => $jenis,
                'jumlah' => $jumlah,
                'keterangan' => mb_substr(sprintf('Koreksi TS-%06d: %s', $original->id, $alasan), 0, 255),
                'saldo' => $saldo + ($jenis === 'masuk' ? $jumlah : -$jumlah),
                'approval_required' => false,
                'reversal_of_id' => $original->id,
            ]);
        });
    }

    /**
     * Withdraw the full remaining balance, e.g. when a student graduates or leaves.
     * Returns null when there is nothing to withdraw.
     */
    public function withdrawAll(Siswa $siswa, string $keterangan): ?Transaksi
    {
        return DB::transaction(function () use ($siswa, $keterangan) {
            Siswa::withTrashed()->lockForUpdate()->findOrFail($siswa->id);

            $saldo = $this->currentBalance($siswa->id);

            if ($saldo <= 0) {
                return null;
            }

            return Transaksi::create([
                'siswa_id' => $siswa->id,
                'tanggal' => today(),
                'jenis' => 'keluar',
                'jumlah' => $saldo,
                'keterangan' => mb_substr($keterangan, 0, 255),
                'saldo' => 0,
                'approval_required' => false,
            ]);
        });
    }

    /**
     * Balance used for new ledger rows. Counts every stored row, so legacy pending
     * withdrawals already reduce the spendable amount.
     */
    public function currentBalance(int $siswaId): float
    {
        return (float) Transaksi::where('siswa_id', $siswaId)
            ->selectRaw(Transaksi::SALDO_EXPRESSION.' as saldo')
            ->value('saldo');
    }
}
