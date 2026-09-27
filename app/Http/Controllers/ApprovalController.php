<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateApprovalRequest;
use App\Models\ApprovalStatus;
use App\Models\Siswa;
use App\Models\Transaksi;
use App\Models\TransaksiApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $status = in_array($request->query('status'), ['pending', 'approved', 'rejected'], true)
            ? $request->query('status')
            : 'pending';

        $items = TransaksiApproval::with([
            'siswa',
            'transaksi.siswa',
            'status',
            'requestedBy',
            'approvedBy',
        ])
            ->whereHas('status', function ($query) use ($status) {
                $query->where('name', $status);
            })
            ->when($status === 'pending', fn ($q) => $q->latest('request_date'), fn ($q) => $q->latest('approval_date'))
            ->paginate(15)
            ->withQueryString()
            ->through(function ($approval) use ($status) {
                $tanggal = $approval->tanggal
                    ?? $approval->transaksi?->tanggal;

                $siswa = $approval->siswa
                    ?? $approval->transaksi?->siswa;

                $jumlah = $approval->jumlah
                    ?? $approval->transaksi?->jumlah
                    ?? 0;

                return [
                    'id' => $approval->id,
                    'transaksiId' => $approval->transaksi_id,
                    'tanggal' => $tanggal?->translatedFormat('d M Y'),
                    'siswa' => $siswa?->nama ?? '-',
                    'saldoSiswa' => $status === 'pending' && $siswa ? $siswa->currentBalance() : null,
                    'jenis' => 'keluar',
                    'jumlah' => (float) $jumlah,
                    'keterangan' => $approval->keterangan ?? $approval->transaksi?->keterangan,
                    'requestedBy' => $approval->requestedBy?->nama ?? '-',
                    'requestDate' => $approval->request_date?->translatedFormat('d M Y H:i'),
                    'status' => $approval->status?->name,
                    'approvedBy' => $approval->approvedBy?->nama,
                    'approvalDate' => $approval->approval_date?->translatedFormat('d M Y H:i'),
                    'rejectionReason' => $approval->rejection_reason,
                ];
            });

        return Inertia::render('Approvals/Index', [
            'items' => $items,
            'status' => $status,
        ]);
    }

    public function update(
        UpdateApprovalRequest $request,
        TransaksiApproval $approval
    ): RedirectResponse {
        $data = $request->validated();

        DB::transaction(function () use (
            $approval,
            $data,
            $request
        ) {
            $approval = TransaksiApproval::query()
                ->lockForUpdate()
                ->findOrFail($approval->id);

            $currentStatus = ApprovalStatus::whereKey(
                $approval->status_id
            )->value('name');

            abort_if(
                $currentStatus !== 'pending',
                409,
                'Approval sudah diproses.'
            );

            $decisionStatus = ApprovalStatus::where(
                'name',
                $data['status']
            )->firstOrFail();

            if ($data['status'] === 'rejected') {
                $legacyTransaction = $approval->transaksi;

                $approval->update([
                    'siswa_id' => $approval->siswa_id
                        ?? $legacyTransaction?->siswa_id,
                    'tanggal' => $approval->tanggal
                        ?? $legacyTransaction?->tanggal,
                    'jumlah' => $approval->jumlah
                        ?? $legacyTransaction?->jumlah,
                    'keterangan' => $approval->keterangan
                        ?? $legacyTransaction?->keterangan,
                    'transaksi_id' => null,
                    'status_id' => $decisionStatus->id,
                    'approved_by' => $request->user('admin')->id,
                    'rejection_reason' => $data['reason'],
                    'approval_date' => now(),
                ]);

                if ($legacyTransaction) {
                    $legacyTransaction->delete();
                }

                return;
            }

            $legacyTransaction = $approval->transaksi;

            $siswaId = $approval->siswa_id
                ?? $legacyTransaction?->siswa_id;

            $jumlah = (float) (
                $approval->jumlah
                ?? $legacyTransaction?->jumlah
                ?? 0
            );

            $tanggal = $approval->tanggal
                ?? $legacyTransaction?->tanggal;

            $keterangan = $approval->keterangan
                ?? $legacyTransaction?->keterangan;

            if (! $siswaId || ! $tanggal || $jumlah <= 0) {
                throw ValidationException::withMessages([
                    'approval' => 'Data pengajuan approval tidak lengkap.',
                ]);
            }

            Siswa::query()
                ->lockForUpdate()
                ->findOrFail($siswaId);

            $saldo = $this->currentBalance($siswaId);

            if ($jumlah > $saldo) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Saldo siswa sudah tidak mencukupi untuk penarikan ini.',
                ]);
            }

            if ($legacyTransaction) {
                $transaction = $legacyTransaction;

                $transaction->update([
                    'saldo' => $saldo - $jumlah,
                    'approval_required' => false,
                ]);
            } else {
                $transaction = Transaksi::create([
                    'siswa_id' => $siswaId,
                    'tanggal' => $tanggal,
                    'jenis' => 'keluar',
                    'jumlah' => $jumlah,
                    'keterangan' => $keterangan,
                    'saldo' => $saldo - $jumlah,
                    'approval_required' => false,
                ]);
            }

            $approval->update([
                'siswa_id' => $siswaId,
                'tanggal' => $tanggal,
                'jumlah' => $jumlah,
                'keterangan' => $keterangan,
                'transaksi_id' => $transaction->id,
                'status_id' => $decisionStatus->id,
                'approved_by' => $request->user('admin')->id,
                'rejection_reason' => null,
                'approval_date' => now(),
            ]);
        });

        return back()->with(
            'success',
            $data['status'] === 'approved'
                ? 'Penarikan berhasil disetujui dan diproses.'
                : 'Pengajuan penarikan berhasil ditolak.'
        );
    }

    private function currentBalance(int $siswaId): float
    {
        return (float) Transaksi::query()
            ->where('siswa_id', $siswaId)
            ->effective()
            ->selectRaw(Transaksi::SALDO_EXPRESSION.' as saldo')
            ->value('saldo');
    }
}
