<?php

namespace App\Http\Controllers;

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
    public function index(): Response
    {
        $items = TransaksiApproval::with([
            'siswa',
            'transaksi.siswa',
            'status',
            'requestedBy',
        ])
            ->whereHas('status', function ($query) {
                $query->where('name', 'pending');
            })
            ->latest('request_date')
            ->paginate(15)
            ->through(function ($approval) {
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
                    'tanggal' => $tanggal?->format('d M Y'),
                    'siswa' => $siswa?->nama ?? '-',
                    'jenis' => 'keluar',
                    'jumlah' => (float) $jumlah,
                    'requestedBy' => $approval->requestedBy?->nama ?? '-',
                    'requestDate' => $approval->request_date?->format(
                        'd M Y H:i'
                    ),
                ];
            });

        return Inertia::render('Approvals/Index', [
            'items' => $items,
        ]);
    }

    public function update(
        Request $request,
        TransaksiApproval $approval
    ): RedirectResponse {
        abort_unless(
            $request->user('admin')?->isAdmin(),
            403
        );

        $data = $request->validate([
            'status' => [
                'required',
                'in:approved,rejected',
            ],
            'reason' => [
                'required_if:status,rejected',
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

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

            if (!$siswaId || !$tanggal || $jumlah <= 0) {
                throw ValidationException::withMessages([
                    'approval' =>
                        'Data pengajuan approval tidak lengkap.',
                ]);
            }

            Siswa::query()
                ->lockForUpdate()
                ->findOrFail($siswaId);

            $saldo = $this->currentBalance($siswaId);

            if ($jumlah > $saldo) {
                throw ValidationException::withMessages([
                    'jumlah' =>
                        'Saldo siswa sudah tidak mencukupi untuk penarikan ini.',
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
            ->where(function ($query) {
                $query
                    ->whereDoesntHave('approval')
                    ->orWhereHas(
                        'approval.status',
                        function ($status) {
                            $status->where(
                                'name',
                                'approved'
                            );
                        }
                    );
            })
            ->selectRaw(
                "COALESCE(
                    SUM(
                        CASE
                            WHEN jenis = 'masuk'
                                THEN jumlah
                            ELSE -jumlah
                        END
                    ),
                    0
                ) AS saldo"
            )
            ->value('saldo');
    }
}