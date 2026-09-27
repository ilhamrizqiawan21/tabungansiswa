<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Siswa;
use App\Models\Transaksi;
use App\Models\TransaksiApproval;
use Illuminate\Contracts\View\View;
use Inertia\Inertia;
use Inertia\Response;

class StudentStatementController extends Controller
{
    public function show(Siswa $siswa): Response
    {
        $siswa->load('kelas.tahunPelajaran');
        $summary = $siswa->transaksi()->effective()->selectRaw(Transaksi::RINGKASAN_EXPRESSION)->first();

        return Inertia::render('Master/Statement', [
            'student' => $siswa,
            'summary' => ['masuk' => (float) $summary->masuk, 'keluar' => (float) $summary->keluar, 'saldo' => (float) $summary->masuk - (float) $summary->keluar],
            'items' => $siswa->transaksi()->effective()->with('reversal:id,reversal_of_id')->latest('id')->paginate(20)->through(fn (Transaksi $t) => [
                'id' => $t->id,
                'tanggal' => $t->tanggal->translatedFormat('d M Y'),
                'jenis' => $t->jenis,
                'jumlah' => (float) $t->jumlah,
                'keterangan' => $t->keterangan,
                'isReversal' => $t->isReversal(),
                'isReversed' => $t->reversal !== null,
            ]),
            'pendingCount' => TransaksiApproval::where('siswa_id', $siswa->id)->whereHas('status', fn ($q) => $q->where('name', 'pending'))->count(),
        ]);
    }

    public function print(Siswa $siswa): View
    {
        $siswa->load('kelas.tahunPelajaran');
        $balance = 0;
        $rows = $siswa->transaksi()->effective()->orderBy('tanggal')->orderBy('id')->get()->map(function ($t) use (&$balance) {
            $balance += $t->jenis === 'masuk' ? (float) $t->jumlah : -(float) $t->jumlah;

            return ['id' => $t->id, 'tanggal' => $t->tanggal->format('d/m/Y'), 'jenis' => $t->jenis, 'jumlah' => (float) $t->jumlah, 'saldo' => $balance, 'keterangan' => $t->keterangan];
        });

        return view('students.statement', ['student' => $siswa, 'rows' => $rows, 'balance' => $balance, 'school' => Setting::get('school_name', 'Tabungan Siswa'), 'teacher' => Setting::get('teacher_name', 'Pengelola')]);
    }
}
