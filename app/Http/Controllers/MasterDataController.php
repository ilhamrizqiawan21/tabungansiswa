<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveSiswaRequest;
use App\Http\Requests\StoreKelasRequest;
use App\Http\Requests\StoreSiswaRequest;
use App\Http\Requests\StoreTahunPelajaranRequest;
use App\Http\Requests\UpdateKelasRequest;
use App\Http\Requests\UpdateSiswaRequest;
use App\Http\Requests\UpdateSiswaStatusRequest;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunPelajaran;
use App\Models\Transaksi;
use App\Services\ActiveSession;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MasterDataController extends Controller
{
    public function tahun(): Response
    {
        return Inertia::render('Master/TahunPelajaran', ['items' => TahunPelajaran::withCount('kelas')->latest('tahun')->latest('semester')->get()]);
    }

    public function storeTahun(StoreTahunPelajaranRequest $request): RedirectResponse
    {
        TahunPelajaran::create($request->validated() + ['status' => 'nonaktif']);

        return back()->with('success', 'Tahun pelajaran berhasil ditambahkan.');
    }

    public function activateTahun(TahunPelajaran $tahun, ActiveSession $session): RedirectResponse
    {
        DB::transaction(fn () => $session->activate($tahun));

        return back()->with('success', 'Tahun pelajaran aktif berhasil diperbarui. Kelas aktif disesuaikan dengan periode ini.');
    }

    public function destroyTahun(TahunPelajaran $tahun): RedirectResponse
    {
        if ($tahun->kelas()->exists()) {
            return back()->with('error', 'Tahun pelajaran yang memiliki kelas tidak dapat dihapus.');
        }

        $tahun->delete();

        return back()->with('success', 'Tahun pelajaran berhasil dihapus.');
    }

    public function kelas(): Response
    {
        return Inertia::render('Master/Kelas', ['items' => Kelas::with('tahunPelajaran')->withCount('siswa')->orderBy('tingkat')->orderBy('nama_kelas')->get()]);
    }

    public function kelasCreate(): Response
    {
        return Inertia::render('Master/KelasForm', ['years' => TahunPelajaran::orderByDesc('tahun')->get(['id', 'tahun', 'semester', 'status'])]);
    }

    public function kelasStore(StoreKelasRequest $request): RedirectResponse
    {
        Kelas::create($request->validated());

        return to_route('master.kelas')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function kelasEdit(Kelas $kelas): Response
    {
        return Inertia::render('Master/KelasForm', ['kelas' => $kelas, 'years' => TahunPelajaran::orderByDesc('tahun')->get(['id', 'tahun', 'semester', 'status'])]);
    }

    public function kelasUpdate(UpdateKelasRequest $request, Kelas $kelas): RedirectResponse
    {
        $kelas->update($request->validated());

        return to_route('master.kelas')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function kelasDestroy(Kelas $kelas): RedirectResponse
    {
        if ($kelas->siswa()->withTrashed()->exists()) {
            return back()->with('error', 'Kelas yang masih memiliki siswa tidak dapat dihapus.');
        }

        $kelas->delete();

        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    public function siswa(ActiveSession $session): Response
    {
        $kelasId = $session->classId();

        return Inertia::render('Master/Siswa', [
            'items' => Siswa::query()
                ->withCount('transaksi')
                ->withSum(['transaksi as saldo_total' => fn ($q) => $q->effective()->selectRaw(Transaksi::SALDO_EXPRESSION)], 'jumlah')
                ->where('kelas_id', $kelasId)
                ->orderBy('nama')
                ->get(['id', 'nis', 'nama', 'kontak', 'status', 'kelas_id']),
            'activeClass' => Kelas::with('tahunPelajaran')->find($kelasId),
            'classes' => Kelas::with('tahunPelajaran:id,tahun,semester')
                ->when($kelasId, fn ($q) => $q->whereKeyNot($kelasId))
                ->orderByDesc('tahun_pelajaran_id')->orderBy('tingkat')->orderBy('nama_kelas')
                ->get(['id', 'nama_kelas', 'tingkat', 'tahun_pelajaran_id'])
                ->map(fn (Kelas $kelas) => ['id' => $kelas->id, 'label' => $kelas->label()]),
        ]);
    }

    public function siswaStore(StoreSiswaRequest $request, ActiveSession $session): RedirectResponse
    {
        $data = $request->validated();
        $data['kelas_id'] = $session->classId();

        if (! $data['kelas_id']) {
            return back()->with('error', 'Atur kelas aktif terlebih dahulu di Pengaturan.');
        }

        Siswa::create($data);

        return back()->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function siswaUpdate(UpdateSiswaRequest $request, Siswa $siswa): RedirectResponse
    {
        $siswa->update($request->validated());

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Graduate / withdraw / reactivate a student. Optionally pays out the full balance
     * so the ledger closes at zero instead of deleting the student.
     */
    public function siswaStatus(UpdateSiswaStatusRequest $request, Siswa $siswa, TransactionService $transactions): RedirectResponse
    {
        $data = $request->validated();
        $label = ['aktif' => 'aktif', 'lulus' => 'lulus', 'keluar' => 'keluar'][$data['status']];

        $payout = DB::transaction(function () use ($data, $siswa, $transactions, $label) {
            $payout = null;

            if ($data['status'] !== 'aktif' && ($data['tarik_saldo'] ?? false)) {
                $payout = $transactions->withdrawAll($siswa, "Penarikan saldo penuh (siswa {$label})");
            }

            $siswa->update(['status' => $data['status']]);

            return $payout;
        });

        $message = "Status {$siswa->nama} diubah menjadi {$label}.";
        if ($payout) {
            $message .= ' Saldo Rp '.number_format((float) $payout->jumlah, 0, ',', '.').' dicatat sebagai penarikan.';
        }

        return back()->with('success', $message);
    }

    /**
     * Move students to another class (e.g. kenaikan kelas). Balances follow the
     * student because the ledger is keyed by student, not by class.
     */
    public function siswaMove(MoveSiswaRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $kelas = Kelas::findOrFail($data['kelas_id']);

        // Updated one by one so each move is written to the audit log.
        $moved = DB::transaction(fn () => Siswa::whereKey($data['siswa_ids'])->get()
            ->each(fn (Siswa $siswa) => $siswa->update(['kelas_id' => $kelas->id]))
            ->count());

        return back()->with('success', "{$moved} siswa dipindahkan ke {$kelas->nama_kelas}. Saldo tabungan ikut berpindah.");
    }

    public function siswaDestroy(Siswa $siswa): RedirectResponse
    {
        if ($siswa->transaksi()->withTrashed()->exists()) {
            return back()->with('error', 'Siswa yang memiliki transaksi tidak dapat dihapus. Ubah statusnya menjadi lulus/keluar.');
        }

        $siswa->delete();

        return back()->with('success', 'Siswa berhasil dihapus.');
    }
}
