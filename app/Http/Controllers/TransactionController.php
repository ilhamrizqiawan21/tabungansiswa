<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Setting;
use App\Models\Siswa;
use App\Models\Transaksi;
use App\Models\TransaksiApproval;
use App\Services\TransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'siswa_id' => ['nullable', 'integer'],
            'jenis' => ['nullable', 'in:masuk,keluar'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', ...($request->filled('start_date') ? ['after_or_equal:start_date'] : [])],
        ]);
        $classId = Setting::get('active_class_id');
        $query = Transaksi::with('siswa')->effective()
            ->whereHas('siswa', fn ($q) => $q->where('kelas_id', $classId))
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->whereHas('siswa', fn ($student) => $student->where(fn ($name) => $name->where('nama', 'like', '%'.$value.'%')->orWhere('nis', 'like', '%'.$value.'%'))))
            ->when($filters['siswa_id'] ?? null, fn ($q, $value) => $q->where('siswa_id', $value))
            ->when($filters['jenis'] ?? null, fn ($q, $value) => $q->where('jenis', $value))
            ->when($filters['start_date'] ?? null, fn ($q, $value) => $q->whereDate('tanggal', '>=', $value))
            ->when($filters['end_date'] ?? null, fn ($q, $value) => $q->whereDate('tanggal', '<=', $value));
        $summary = (clone $query)->selectRaw("COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE 0 END), 0) as masuk, COALESCE(SUM(CASE WHEN jenis = 'keluar' THEN jumlah ELSE 0 END), 0) as keluar")->first();

        return Inertia::render('Transactions/Index', [
            'students' => Siswa::where('kelas_id', $classId)->orderBy('nama')->get(['id', 'nis', 'nama']),
            'filters' => $filters,
            'summary' => ['masuk' => (float) $summary->masuk, 'keluar' => (float) $summary->keluar],
            'items' => $query->latest('id')->paginate(15)->withQueryString()->through(fn ($t) => ['id' => $t->id, 'siswa_id' => $t->siswa_id, 'tanggal' => $t->tanggal->format('d M Y'), 'siswa' => $t->siswa?->nama ?? '-', 'jenis' => $t->jenis, 'jumlah' => (float) $t->jumlah, 'saldo' => (float) $t->saldo, 'keterangan' => $t->keterangan]),
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

    public function create(): Response
    {
        $classId = Setting::get('active_class_id');

        return Inertia::render('Transactions/Create', ['students' => Siswa::where('kelas_id', $classId)->withSum(['transaksi as saldo' => function ($q) {
            $q->selectRaw("SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END)");
        }], 'jumlah')->orderBy('nama')->get(['id', 'nis', 'nama']), 'approvalThreshold' => config('tabungan.approval_threshold'), 'activeClass' => Kelas::with('tahunPelajaran')->find($classId)]);
    }

    public function store(Request $request, TransactionService $service): RedirectResponse
    {
        $data = $request->validate(['siswa_id' => ['required', 'exists:siswa,id'], 'tanggal' => ['required', 'date'], 'jenis' => ['required', 'in:masuk,keluar'], 'jumlah' => ['required', 'numeric', 'min:1'], 'keterangan' => ['nullable', 'string', 'max:255']]);
        if (! Siswa::whereKey($data['siswa_id'])->where('kelas_id', Setting::get('active_class_id'))->exists()) {
            throw ValidationException::withMessages(['siswa_id' => 'Siswa bukan bagian dari kelas aktif. Pilih ulang siswa.']);
        }
        $data['requested_by'] = $request->user('admin')->id;
        $result = $service->create($data);

        return to_route('transactions.index')->with('success', $result instanceof TransaksiApproval ? 'Pengajuan penarikan dikirim. Saldo belum dikurangi; menunggu persetujuan admin.' : 'Transaksi berhasil dicatat.');
    }
}
