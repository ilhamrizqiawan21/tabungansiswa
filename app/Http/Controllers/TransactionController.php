<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterTransaksiRequest;
use App\Http\Requests\ReverseTransaksiRequest;
use App\Http\Requests\StoreTransaksiRequest;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\Siswa;
use App\Models\Transaksi;
use App\Models\TransaksiApproval;
use App\Services\ActiveSession;
use App\Services\TransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(FilterTransaksiRequest $request, ActiveSession $session): Response
    {
        $filters = $request->validated();
        $classId = $session->classId();
        $query = Transaksi::with(['siswa', 'reversal:id,reversal_of_id'])->effective()
            ->whereHas('siswa', fn ($q) => $q->where('kelas_id', $classId))
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->whereHas('siswa', fn ($student) => $student->where(fn ($name) => $name->where('nama', 'like', '%'.$value.'%')->orWhere('nis', 'like', '%'.$value.'%'))))
            ->when($filters['siswa_id'] ?? null, fn ($q, $value) => $q->where('siswa_id', $value))
            ->when($filters['jenis'] ?? null, fn ($q, $value) => $q->where('jenis', $value))
            ->when($filters['start_date'] ?? null, fn ($q, $value) => $q->whereDate('tanggal', '>=', $value))
            ->when($filters['end_date'] ?? null, fn ($q, $value) => $q->whereDate('tanggal', '<=', $value));
        $summary = (clone $query)->selectRaw(Transaksi::RINGKASAN_EXPRESSION)->first();

        return Inertia::render('Transactions/Index', [
            'filters' => $filters,
            'summary' => ['masuk' => (float) $summary->masuk, 'keluar' => (float) $summary->keluar],
            'items' => $query->latest('id')->paginate(15)->withQueryString()->through(fn (Transaksi $t) => [
                'id' => $t->id,
                'siswa_id' => $t->siswa_id,
                'tanggal' => $t->tanggal->translatedFormat('d M Y'),
                'siswa' => $t->siswa?->nama ?? '-',
                'jenis' => $t->jenis,
                'jumlah' => (float) $t->jumlah,
                'saldo' => (float) $t->saldo,
                'keterangan' => $t->keterangan,
                'isReversal' => $t->isReversal(),
                'isReversed' => $t->reversal !== null,
            ]),
        ]);
    }

    public function receipt(Transaksi $transaksi): View
    {
        abort_unless(Transaksi::effective()->whereKey($transaksi->id)->exists(), 404);
        $transaksi->load('siswa.kelas');

        return view('transactions.receipt', [
            'transaction' => $transaksi,
            'school' => Setting::get('school_name', 'Tabungan Siswa'),
            'teacher' => Setting::get('teacher_name', 'Pengelola'),
        ]);
    }

    public function create(ActiveSession $session): Response
    {
        $classId = $session->classId();

        return Inertia::render('Transactions/Create', [
            'students' => Siswa::aktif()->where('kelas_id', $classId)
                ->withSum(['transaksi as saldo' => fn ($q) => $q->selectRaw(Transaksi::SALDO_EXPRESSION)], 'jumlah')
                ->orderBy('nama')->get(['id', 'nis', 'nama']),
            'approvalThreshold' => config('tabungan.approval_threshold'),
            'maxJumlah' => StoreTransaksiRequest::MAX_JUMLAH,
            'activeClass' => Kelas::with('tahunPelajaran')->find($classId),
        ]);
    }

    public function store(StoreTransaksiRequest $request, TransactionService $service, ActiveSession $session): RedirectResponse
    {
        $data = $request->validated();
        if (! Siswa::whereKey($data['siswa_id'])->where('kelas_id', $session->classId())->exists()) {
            throw ValidationException::withMessages(['siswa_id' => 'Siswa bukan bagian dari kelas aktif. Pilih ulang siswa.']);
        }
        $data['requested_by'] = $request->user('admin')->id;
        $result = $service->create($data);

        $message = $result instanceof TransaksiApproval
            ? 'Pengajuan penarikan dikirim. Saldo belum dikurangi; menunggu persetujuan admin.'
            : 'Transaksi berhasil dicatat.';

        if ($request->boolean('lanjut')) {
            return to_route('transactions.create', ['tanggal' => $data['tanggal'], 'jenis' => $data['jenis']])->with('success', $message);
        }

        return to_route('transactions.index')->with('success', $message);
    }

    public function reverse(ReverseTransaksiRequest $request, Transaksi $transaksi, TransactionService $service): RedirectResponse
    {
        $reversal = $service->reverse($transaksi, $request->validated('alasan'));

        return back()->with('success', sprintf('Transaksi TS-%06d dibatalkan dengan transaksi koreksi TS-%06d.', $transaksi->id, $reversal->id));
    }
}
