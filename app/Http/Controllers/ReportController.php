<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterReportRequest;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\Siswa;
use App\Models\Transaksi;
use App\Services\ActiveSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private function query(FilterReportRequest $request): Builder
    {
        $data = $request->validated();

        return Transaksi::with('siswa')
            ->effective()
            ->when($data['start_date'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '>=', $v))
            ->when($data['end_date'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '<=', $v))
            ->when($data['jenis'] ?? null, fn ($q, $v) => $q->where('jenis', $v))
            ->when($data['siswa_id'] ?? null, fn ($q, $v) => $q->where('siswa_id', $v))
            ->when($data['kelas_id'] ?? null, fn ($q, $v) => $q->whereHas('siswa', fn ($s) => $s->withTrashed()->where('kelas_id', $v)))
            ->latest('id');
    }

    public function index(FilterReportRequest $request): Response
    {
        $query = $this->query($request);

        $summary = (clone $query)
            ->selectRaw(Transaksi::RINGKASAN_EXPRESSION.', COUNT(*) as count')
            ->first();

        return Inertia::render('Reports/Index', [
            'items' => $query->paginate(20)->withQueryString()->through(fn ($t) => [
                'id' => $t->id,
                'tanggal' => $t->tanggal->translatedFormat('d M Y'),
                'siswa' => $t->siswa?->nama ?? '-',
                'jenis' => $t->jenis,
                'jumlah' => (float) $t->jumlah,
                'saldo' => (float) $t->saldo,
                'keterangan' => $t->keterangan,
            ]),
            'filters' => $request->only(['start_date', 'end_date', 'jenis', 'kelas_id', 'siswa_id']),
            'summary' => [
                'masuk' => (float) $summary->masuk,
                'keluar' => (float) $summary->keluar,
                'count' => (int) $summary->count,
            ],
            'classes' => $this->classOptions(),
            'students' => Siswa::withTrashed()
                ->when($request->validated('kelas_id'), fn ($q, $v) => $q->where('kelas_id', $v), fn ($q) => $q->whereRaw('1 = 0'))
                ->orderBy('nama')->get(['id', 'nis', 'nama']),
        ]);
    }

    public function export(FilterReportRequest $request): StreamedResponse
    {
        $rows = $this->query($request)->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tanggal', 'NIS', 'Nama Siswa', 'Jenis', 'Jumlah', 'Saldo', 'Keterangan']);
            foreach ($rows as $t) {
                fputcsv($out, [$t->tanggal->format('Y-m-d'), $t->siswa?->nis ?? '-', $t->siswa?->nama ?? '-', $t->jenis, $t->jumlah, $t->saldo, $t->keterangan ?? '']);
            }
            fclose($out);
        }, 'laporan-transaksi-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(FilterReportRequest $request): View
    {
        $rows = $this->query($request)->get();

        return view('reports.print', [
            'rows' => $rows,
            'summary' => [
                'masuk' => $rows->where('jenis', 'masuk')->sum('jumlah'),
                'keluar' => $rows->where('jenis', 'keluar')->sum('jumlah'),
            ],
        ]);
    }

    public function exportXlsx(FilterReportRequest $request): StreamedResponse
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->fromArray([['Tanggal', 'NIS', 'Nama Siswa', 'Jenis', 'Jumlah', 'Saldo', 'Keterangan']], null, 'A1');
        $row = 2;
        foreach ($this->query($request)->get() as $t) {
            $sheet->fromArray([[
                $t->tanggal->format('Y-m-d'),
                $t->siswa?->nis ?? '-',
                $t->siswa?->nama ?? '-',
                $t->jenis === 'masuk' ? 'Setoran' : 'Penarikan',
                (float) $t->jumlah,
                (float) $t->saldo,
                $t->keterangan ?? '',
            ]], null, 'A'.$row++);
        }
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        foreach (['A' => 15, 'B' => 15, 'C' => 28, 'D' => 15, 'E' => 16, 'F' => 16, 'G' => 32] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        return $this->streamXlsx($book, 'laporan-transaksi-'.now()->format('Ymd-His').'.xlsx');
    }

    public function exportPdf(FilterReportRequest $request): HttpResponse
    {
        $rows = $this->query($request)->get();

        return Pdf::loadView('reports.print', [
            'rows' => $rows,
            'summary' => [
                'masuk' => $rows->where('jenis', 'masuk')->sum('jumlah'),
                'keluar' => $rows->where('jenis', 'keluar')->sum('jumlah'),
            ],
        ])->setPaper('a4', 'landscape')->download('laporan-transaksi-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Balance recap of every student in one class.
     */
    public function recap(Request $request, ActiveSession $session): Response
    {
        $kelas = $this->recapClass($request, $session);
        $rows = $kelas ? $this->recapRows($kelas) : collect();

        return Inertia::render('Reports/Recap', [
            'kelas' => $kelas ? ['id' => $kelas->id, 'label' => $kelas->label()] : null,
            'classes' => $this->classOptions(),
            'rows' => $rows,
            'totals' => [
                'masuk' => $rows->sum('masuk'),
                'keluar' => $rows->sum('keluar'),
                'saldo' => $rows->sum('saldo'),
            ],
        ]);
    }

    public function recapPrint(Request $request, ActiveSession $session): View
    {
        $kelas = $this->recapClass($request, $session);
        abort_unless($kelas, 404);
        $rows = $this->recapRows($kelas);

        return view('reports.recap', [
            'kelas' => $kelas,
            'rows' => $rows,
            'school' => Setting::get('school_name', 'Tabungan Siswa'),
            'teacher' => Setting::get('teacher_name', 'Pengelola'),
        ]);
    }

    public function recapXlsx(Request $request, ActiveSession $session): StreamedResponse
    {
        $kelas = $this->recapClass($request, $session);
        abort_unless($kelas, 404);

        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Rekap saldo');
        $sheet->fromArray([['NIS', 'Nama Siswa', 'Status', 'Total Setoran', 'Total Penarikan', 'Saldo']], null, 'A1');
        $row = 2;
        foreach ($this->recapRows($kelas) as $item) {
            $sheet->setCellValueExplicit([1, $row], $item['nis'], DataType::TYPE_STRING);
            $sheet->fromArray([[$item['nama'], $item['status'], $item['masuk'], $item['keluar'], $item['saldo']]], null, 'B'.$row++);
        }
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        foreach (['A' => 15, 'B' => 32, 'C' => 12, 'D' => 18, 'E' => 18, 'F' => 18] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        return $this->streamXlsx($book, 'rekap-saldo-'.str($kelas->nama_kelas)->slug().'-'.now()->format('Ymd').'.xlsx');
    }

    private function recapClass(Request $request, ActiveSession $session): ?Kelas
    {
        $id = $request->integer('kelas_id') ?: $session->classId();

        return $id ? Kelas::with('tahunPelajaran')->find($id) : null;
    }

    /**
     * @return Collection<int, array{id: int, nis: string, nama: string, status: string, masuk: float, keluar: float, saldo: float}>
     */
    private function recapRows(Kelas $kelas): Collection
    {
        return Siswa::where('kelas_id', $kelas->id)
            ->withSum(['transaksi as total_masuk' => fn ($q) => $q->effective()->where('jenis', 'masuk')], 'jumlah')
            ->withSum(['transaksi as total_keluar' => fn ($q) => $q->effective()->where('jenis', 'keluar')], 'jumlah')
            ->orderBy('nama')
            ->get()
            ->map(fn (Siswa $siswa) => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'nama' => $siswa->nama,
                'status' => $siswa->status,
                'masuk' => (float) $siswa->total_masuk,
                'keluar' => (float) $siswa->total_keluar,
                'saldo' => (float) $siswa->total_masuk - (float) $siswa->total_keluar,
            ]);
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    private function classOptions(): Collection
    {
        return Kelas::with('tahunPelajaran:id,tahun,semester')
            ->orderByDesc('tahun_pelajaran_id')->orderBy('tingkat')->orderBy('nama_kelas')
            ->get()
            ->map(fn (Kelas $kelas) => ['id' => $kelas->id, 'label' => $kelas->label()]);
    }

    private function streamXlsx(Spreadsheet $book, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($book) {
            try {
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
