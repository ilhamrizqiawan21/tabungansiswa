<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\Transaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_summary_matches_filtered_transactions(): void
    {
        $siswa = Siswa::create(['nis' => '00123', 'nama' => 'Budi']);

        Transaksi::create(['siswa_id' => $siswa->id, 'tanggal' => '2026-01-10', 'jenis' => 'masuk', 'jumlah' => 50000, 'saldo' => 50000]);
        Transaksi::create(['siswa_id' => $siswa->id, 'tanggal' => '2026-01-15', 'jenis' => 'masuk', 'jumlah' => 30000, 'saldo' => 80000]);
        Transaksi::create(['siswa_id' => $siswa->id, 'tanggal' => '2026-01-20', 'jenis' => 'keluar', 'jumlah' => 20000, 'saldo' => 60000]);
        // Outside the date filter below; must not affect the summary.
        Transaksi::create(['siswa_id' => $siswa->id, 'tanggal' => '2026-02-01', 'jenis' => 'masuk', 'jumlah' => 999999, 'saldo' => 1059999]);

        $this->get('/laporan?start_date=2026-01-01&end_date=2026-01-31')
            ->assertInertia(fn (Assert $page) => $page->component('Reports/Index')
                ->where('summary.masuk', 80000)
                ->where('summary.keluar', 20000)
                ->where('summary.count', 3)
                ->has('items.data', 3));
    }

    public function test_class_filter_excludes_other_classes(): void
    {
        $inClass = Siswa::factory()->create();
        Transaksi::factory()->for($inClass)->setoran(10000)->create();
        Transaksi::factory()->setoran(99000)->create();

        $this->get('/laporan?kelas_id='.$inClass->kelas_id)
            ->assertInertia(fn (Assert $page) => $page->where('summary.masuk', 10000)->has('students', 1));
    }

    public function test_recap_lists_balance_per_student_of_selected_class(): void
    {
        $budi = Siswa::factory()->create(['nama' => 'Budi']);
        Siswa::factory()->create(['nama' => 'Ani', 'kelas_id' => $budi->kelas_id]);
        Transaksi::factory()->for($budi)->setoran(50000)->create();
        Transaksi::factory()->for($budi)->penarikan(20000)->create(['saldo' => 30000]);
        Transaksi::factory()->setoran(70000)->create();

        $this->get('/laporan/rekap?kelas_id='.$budi->kelas_id)
            ->assertInertia(fn (Assert $page) => $page->component('Reports/Recap')
                ->has('rows', 2)
                ->where('rows.0.nama', 'Ani')->where('rows.0.saldo', 0)
                ->where('rows.1.saldo', 30000)
                ->where('totals.saldo', 30000));
    }
}
