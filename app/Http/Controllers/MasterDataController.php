<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Setting;
use App\Models\Siswa;
use App\Models\TahunPelajaran;
use App\Models\Transaksi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterDataController extends Controller
{
    public function tahun(): Response
    {
        return Inertia::render('Master/TahunPelajaran', ['items' => TahunPelajaran::withCount('kelas')->latest('tahun')->latest('semester')->get()]);
    }

    public function storeTahun(Request $request): RedirectResponse
    {
        $data = $request->validate(['tahun' => ['required', 'regex:/^\d{4}\/\d{4}$/'], 'semester' => ['required', 'in:ganjil,genap']]);
        TahunPelajaran::create($data + ['status' => 'nonaktif']);

        return back()->with('success', 'Tahun pelajaran berhasil ditambahkan.');
    }

    public function activateTahun(TahunPelajaran $tahun): RedirectResponse
    {
        TahunPelajaran::query()->update(['status' => 'nonaktif']);
        $tahun->update(['status' => 'aktif']);

        return back()->with('success', 'Tahun pelajaran aktif berhasil diperbarui.');
    }

    public function destroyTahun(TahunPelajaran $tahun): RedirectResponse
    {
        if ($tahun->kelas()->exists()) {
            return back()->with('error', 'Tahun pelajaran yang memiliki kelas tidak dapat dihapus.');
        } $tahun->delete();

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

    public function kelasStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['nama_kelas' => ['required', 'string', 'max:50'], 'tingkat' => ['required', 'string', 'max:10'], 'jurusan' => ['nullable', 'string', 'max:50'], 'tahun_pelajaran_id' => ['required', 'exists:tahun_pelajaran,id']]);
        Kelas::create($data);

        return to_route('master.kelas')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function kelasEdit(Kelas $kelas): Response
    {
        return Inertia::render('Master/KelasForm', ['kelas' => $kelas, 'years' => TahunPelajaran::orderByDesc('tahun')->get(['id', 'tahun', 'semester', 'status'])]);
    }

    public function kelasUpdate(Request $request, Kelas $kelas): RedirectResponse
    {
        $data = $request->validate(['nama_kelas' => ['required', 'string', 'max:50'], 'tingkat' => ['required', 'string', 'max:10'], 'jurusan' => ['nullable', 'string', 'max:50'], 'tahun_pelajaran_id' => ['required', 'exists:tahun_pelajaran,id']]);
        $kelas->update($data);

        return to_route('master.kelas')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function kelasDestroy(Kelas $kelas): RedirectResponse
    {
        if ($kelas->siswa()->exists()) {
            return back()->with('error', 'Kelas yang masih memiliki siswa tidak dapat dihapus.');
        } $kelas->delete();

        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    public function siswa(): Response
    {
        $kelasId = Setting::get('active_class_id');

        return Inertia::render('Master/Siswa', ['items' => Siswa::with('kelas.tahunPelajaran')->withCount('transaksi')->withSum(['transaksi as saldo_total' => function ($q) {
            $q->selectRaw("SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END)");
        }], 'jumlah')->when($kelasId, fn ($q) => $q->where('kelas_id', $kelasId))->orderBy('nama')->get(), 'activeClass' => Kelas::with('tahunPelajaran')->find($kelasId), 'transactions' => Transaksi::with('siswa')->whereHas('siswa', fn ($q) => $q->where('kelas_id', $kelasId))->latest('id')->limit(20)->get()->map(fn ($t) => ['id' => $t->id, 'tanggal' => $t->tanggal->format('d M Y'), 'siswa' => $t->siswa?->nama, 'jenis' => $t->jenis, 'jumlah' => (float) $t->jumlah, 'saldo' => (float) $t->saldo])]);
    }

    public function siswaStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['nis' => ['required', 'string', 'max:20', 'unique:siswa,nis'], 'nama' => ['required', 'string', 'max:100'], 'kontak' => ['nullable', 'string', 'max:25']]);
        $data['kelas_id'] = Setting::get('active_class_id');
        if (! $data['kelas_id']) {
            return back()->with('error', 'Atur kelas aktif terlebih dahulu di Pengaturan.');
        } Siswa::create($data);

        return back()->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function siswaUpdate(Request $request, Siswa $siswa): RedirectResponse
    {
        $data = $request->validate(['nis' => ['required', 'string', 'max:20', 'unique:siswa,nis,'.$siswa->id], 'nama' => ['required', 'string', 'max:100'], 'kontak' => ['nullable', 'string', 'max:25']]);
        $siswa->update($data);

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function siswaDestroy(Siswa $siswa): RedirectResponse
    {
        if ($siswa->transaksi()->exists()) {
            return back()->with('error', 'Siswa yang memiliki transaksi tidak dapat dihapus.');
        } $siswa->delete();

        return back()->with('success','Siswa berhasil dihapus.');
    }
}
