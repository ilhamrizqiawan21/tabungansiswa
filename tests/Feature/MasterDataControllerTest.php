<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\Siswa;
use App\Models\TahunPelajaran;
use App\Models\Transaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_graduating_student_with_payout_closes_balance_at_zero(): void
    {
        $siswa = Siswa::factory()->create();
        Transaksi::factory()->for($siswa)->setoran(75000)->create();

        $this->patch("/master/siswa/{$siswa->id}/status", ['status' => 'lulus', 'tarik_saldo' => true])
            ->assertRedirect()
            ->assertSessionHas('success', "Status {$siswa->nama} diubah menjadi lulus. Saldo Rp 75.000 dicatat sebagai penarikan.");

        $this->assertSame('lulus', $siswa->fresh()->status);
        $this->assertSame(0.0, $siswa->currentBalance());
    }

    public function test_status_change_without_payout_keeps_balance(): void
    {
        $siswa = Siswa::factory()->create();
        Transaksi::factory()->for($siswa)->setoran(75000)->create();

        $this->patch("/master/siswa/{$siswa->id}/status", ['status' => 'keluar'])->assertSessionHasNoErrors();

        $this->assertSame(75000.0, $siswa->currentBalance());
    }

    public function test_moving_students_keeps_their_balance_and_logs_each_move(): void
    {
        $target = Kelas::factory()->create(['nama_kelas' => 'VIII-A']);
        $students = Siswa::factory()->count(2)->create();
        Transaksi::factory()->for($students[0])->setoran(40000)->create();

        $this->post('/master/siswa/pindah-kelas', ['siswa_ids' => $students->pluck('id')->all(), 'kelas_id' => $target->id])
            ->assertSessionHas('success', '2 siswa dipindahkan ke VIII-A. Saldo tabungan ikut berpindah.');

        $this->assertSame(2, Siswa::where('kelas_id', $target->id)->count());
        $this->assertSame(40000.0, $students[0]->currentBalance());
        $this->assertSame(2, AuditLog::where('table_name', 'siswa')->where('action', 'UPDATE')->count());
    }

    public function test_move_requires_students_and_target_class(): void
    {
        $this->post('/master/siswa/pindah-kelas', [])->assertSessionHasErrors(['siswa_ids', 'kelas_id']);
    }

    public function test_duplicate_class_name_in_same_year_is_rejected(): void
    {
        $existing = Kelas::factory()->create(['nama_kelas' => 'VII-A']);

        $this->post('/master/kelas', ['nama_kelas' => 'VII-A', 'tingkat' => 'VII', 'tahun_pelajaran_id' => $existing->tahun_pelajaran_id])
            ->assertSessionHasErrors(['nama_kelas' => 'Nama kelas sudah digunakan.']);
    }

    public function test_same_class_name_is_allowed_in_another_year(): void
    {
        Kelas::factory()->create(['nama_kelas' => 'VII-A']);
        $year = TahunPelajaran::factory()->create();

        $this->post('/master/kelas', ['nama_kelas' => 'VII-A', 'tingkat' => 'VII', 'tahun_pelajaran_id' => $year->id])
            ->assertSessionHasNoErrors();
    }

    public function test_activating_year_switches_active_class_to_a_class_of_that_year(): void
    {
        $old = Kelas::factory()->create();
        Setting::put('active_class_id', (string) $old->id);
        $newClass = Kelas::factory()->create();

        $this->patch("/master/tahun-pelajaran/{$newClass->tahun_pelajaran_id}/aktifkan")->assertRedirect();

        $this->assertSame((string) $newClass->id, Setting::get('active_class_id'));
        $this->assertSame('aktif', $newClass->tahunPelajaran->fresh()->status);
        $this->assertSame('nonaktif', $old->tahunPelajaran->fresh()->status);
    }

    public function test_student_with_transactions_cannot_be_deleted(): void
    {
        $siswa = Siswa::factory()->create();
        Transaksi::factory()->for($siswa)->create();

        $this->delete("/master/siswa/{$siswa->id}")->assertSessionHas('error');

        $this->assertNotSoftDeleted($siswa);
    }

    public function test_deleting_student_without_transactions_soft_deletes(): void
    {
        $siswa = Siswa::factory()->create();

        $this->delete("/master/siswa/{$siswa->id}")->assertSessionHas('success');

        $this->assertSoftDeleted($siswa);
    }
}
