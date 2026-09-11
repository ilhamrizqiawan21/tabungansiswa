<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ApprovalStatus;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\Siswa;
use App\Models\Transaksi;
use App\Models\TransaksiApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DailyManagementTest extends TestCase
{
    use RefreshDatabase;

    private function student(): Siswa
    {
        $class = Kelas::create(['nama_kelas' => 'VII A', 'tingkat' => 'VII']);
        Setting::put('active_class_id', (string) $class->id);

        return Siswa::create(['nis' => '00123', 'nama' => 'Budi', 'kelas_id' => $class->id]);
    }

    public function test_transaction_filters_and_totals_respect_active_class_and_pagination(): void
    {
        $student = $this->student();
        foreach (range(1, 16) as $i) {
            Transaksi::create(['siswa_id' => $student->id, 'tanggal' => '2026-09-10', 'jenis' => 'masuk', 'jumlah' => 10000, 'saldo' => $i * 10000]);
        }
        Transaksi::create(['siswa_id' => $student->id, 'tanggal' => '2026-08-01', 'jenis' => 'masuk', 'jumlah' => 20000, 'saldo' => 180000]);
        Transaksi::create(['siswa_id' => $student->id, 'tanggal' => '2026-09-10', 'jenis' => 'keluar', 'jumlah' => 5000, 'saldo' => 175000]);
        $other = Siswa::create(['nis' => '999', 'nama' => 'Budi luar kelas']);
        Transaksi::create(['siswa_id' => $other->id, 'tanggal' => '2026-09-10', 'jenis' => 'masuk', 'jumlah' => 30000, 'saldo' => 30000]);

        $this->get('/transaksi?search=Budi&jenis=masuk&start_date=2026-09-01&end_date=2026-09-30')
            ->assertInertia(fn (Assert $page) => $page->component('Transactions/Index')
                ->where('items.total', 16)->has('items.data', 15)
                ->where('summary.masuk', 160000)->where('summary.keluar', 0)
                ->where('items.next_page_url', fn ($url) => str_contains($url, 'search=Budi') && str_contains($url, 'page=2')));
    }

    public function test_search_by_nis_and_invalid_dates_are_handled(): void
    {
        $student = $this->student();
        Transaksi::create(['siswa_id' => $student->id, 'tanggal' => '2026-09-10', 'jenis' => 'masuk', 'jumlah' => 10000, 'saldo' => 10000]);

        $this->get('/transaksi?search=00123&siswa_id='.$student->id)->assertInertia(fn (Assert $page) => $page->has('items.data', 1));
        $this->get('/transaksi?end_date=2026-09-30')->assertSessionHasNoErrors()->assertInertia(fn (Assert $page) => $page->has('items.data', 1));
        $this->from('/transaksi')->get('/transaksi?start_date=2026-09-30&end_date=2026-09-01')->assertSessionHasErrors('end_date');
    }

    public function test_student_statement_excludes_unapproved_withdrawals_and_other_students(): void
    {
        $student = $this->student();
        Transaksi::create(['siswa_id' => $student->id, 'tanggal' => '2026-09-10', 'jenis' => 'masuk', 'jumlah' => 50000, 'saldo' => 50000]);
        $pending = Transaksi::create(['siswa_id' => $student->id, 'tanggal' => '2026-09-11', 'jenis' => 'keluar', 'jumlah' => 30000, 'saldo' => 20000]);
        $admin = Admin::create(['username' => 'admin', 'password' => bcrypt('test'), 'nama' => 'Admin', 'role' => 'admin']);
        $status = ApprovalStatus::create(['name' => 'pending']);
        TransaksiApproval::create(['siswa_id' => $student->id, 'transaksi_id' => $pending->id, 'requested_by' => $admin->id, 'status_id' => $status->id]);
        $other = Siswa::create(['nis' => '999', 'nama' => 'Siswa lain']);
        Transaksi::create(['siswa_id' => $other->id, 'tanggal' => '2026-09-10', 'jenis' => 'masuk', 'jumlah' => 90000, 'saldo' => 90000]);

        $this->get('/master/siswa/'.$student->id.'/buku')->assertInertia(fn (Assert $page) => $page
            ->component('Master/Statement')->where('student.nis', '00123')
            ->where('summary.saldo', 50000)->where('summary.keluar', 0)->where('pendingCount', 1)->has('items.data', 1));
        $this->get('/transaksi/'.$pending->id.'/bukti')->assertNotFound();
    }

    public function test_printed_book_recalculates_chronological_balance_and_receipt_escapes_notes(): void
    {
        $student = $this->student();
        $later = Transaksi::create(['siswa_id' => $student->id, 'tanggal' => '2026-09-10', 'jenis' => 'masuk', 'jumlah' => 20000, 'saldo' => 20000, 'keterangan' => '<script>alert(1)</script>']);
        $earlier = Transaksi::create(['siswa_id' => $student->id, 'tanggal' => '2026-09-01', 'jenis' => 'masuk', 'jumlah' => 10000, 'saldo' => 30000]);

        $this->get('/master/siswa/'.$student->id.'/buku/cetak')->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows[0]['id'] === $earlier->id && $rows[0]['saldo'] === 10000.0 && $rows[1]['saldo'] === 30000.0)
            ->assertSee('Buku Tabungan Siswa')->assertSee('00123');
        $this->get('/transaksi/'.$later->id.'/bukti')->assertOk()
            ->assertSee(sprintf('TS-%06d', $later->id))->assertSee('20.000')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }
}
