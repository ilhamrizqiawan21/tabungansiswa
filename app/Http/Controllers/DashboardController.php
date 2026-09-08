<?php
namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $start = now()->startOfMonth()->subMonths(5);
        $months = collect(range(0, 5))->map(fn ($i) => $start->copy()->addMonths($i));
        $monthly = $months->map(function ($month) {
            $from = $month->copy()->startOfMonth(); $to = $month->copy()->endOfMonth();
            return ['label' => $month->translatedFormat('M'), 'masuk' => (float) Transaksi::whereBetween('tanggal', [$from, $to])->where('jenis', 'masuk')->sum('jumlah'), 'keluar' => (float) Transaksi::whereBetween('tanggal', [$from, $to])->where('jenis', 'keluar')->sum('jumlah')];
        })->values();
        $types = Transaksi::select('jenis', DB::raw('COUNT(*) as jumlah'), DB::raw('COALESCE(SUM(jumlah), 0) as total'))->groupBy('jenis')->get()->keyBy('jenis');
        return Inertia::render('Dashboard', [
            'appName' => config('app.name', 'Tabungan Siswa'),
            'stats' => ['totalSiswa' => Siswa::count(), 'saldo' => (float) DB::table('transaksi')->selectRaw("COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END), 0) as total")->value('total'), 'transaksiHariIni' => Transaksi::whereDate('tanggal', today())->count(), 'totalTransaksi' => Transaksi::count()],
            'monthly' => $monthly,
            'types' => ['masuk' => ['jumlah' => (int) ($types['masuk']->jumlah ?? 0), 'total' => (float) ($types['masuk']->total ?? 0)], 'keluar' => ['jumlah' => (int) ($types['keluar']->jumlah ?? 0), 'total' => (float) ($types['keluar']->total ?? 0)]],
            'recentTransactions' => Transaksi::with('siswa')->latest('id')->limit(6)->get()->map(fn ($t) => ['id' => $t->id, 'tanggal' => $t->tanggal->format('d M Y'), 'siswa' => $t->siswa?->nama ?? '-', 'jenis' => $t->jenis, 'jumlah' => (float) $t->jumlah, 'saldo' => (float) $t->saldo]),
        ]);
    }
}
