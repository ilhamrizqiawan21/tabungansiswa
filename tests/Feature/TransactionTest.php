<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ApprovalStatus;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\Siswa;
use App\Models\TahunPelajaran;
use App\Models\Transaksi;
use App\Models\TransaksiApproval;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Admin $admin;

    private Siswa $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'username' => 'transaction-admin',
            'password' => bcrypt('test-password'),
            'nama' => 'Pengelola',
            'role' => 'admin',
        ]);

        ApprovalStatus::insert([
            ['name' => 'pending', 'description' => null],
            ['name' => 'approved', 'description' => null],
            ['name' => 'rejected', 'description' => null],
        ]);

        $year = TahunPelajaran::create([
            'tahun' => '2026/2027',
            'semester' => 'ganjil',
            'status' => 'aktif',
        ]);
        $class = Kelas::create([
            'nama_kelas' => 'VII A',
            'tingkat' => 'VII',
            'tahun_pelajaran_id' => $year->id,
        ]);
        Setting::put('active_class_id', (string) $class->id);
        $this->siswa = Siswa::create([
            'nis' => 'S-001',
            'nama' => 'Budi',
            'kelas_id' => $class->id,
        ]);
    }

    public function test_deposit_creates_transaction_with_updated_balance(): void
    {
        $this
            ->post('/transaksi', [
                'siswa_id' => $this->siswa->id,
                'tanggal' => '2026-09-11',
                'jenis' => 'masuk',
                'jumlah' => 500000,
            ])
            ->assertRedirect('/transaksi')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('transaksi', [
            'siswa_id' => $this->siswa->id,
            'jenis' => 'masuk',
            'saldo' => 500000,
        ]);
        $this->assertDatabaseHas('audit_log', [
            'admin_id' => $this->admin->id,
            'table_name' => 'transaksi',
            'action' => 'CREATE',
        ]);
    }

    public function test_transaction_form_receives_balance_and_configured_threshold(): void
    {
        config(['tabungan.approval_threshold' => 250000]);
        Transaksi::create([
            'siswa_id' => $this->siswa->id,
            'tanggal' => '2026-09-10',
            'jenis' => 'masuk',
            'jumlah' => 500000,
            'saldo' => 500000,
        ]);

        $this->get('/transaksi/create')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Transactions/Create')
                ->where('approvalThreshold', 250000)
                ->where('students.0.saldo', fn ($saldo) => (float) $saldo === 500000.0));

        $this->post('/transaksi', [
            'siswa_id' => $this->siswa->id,
            'tanggal' => '2026-09-11',
            'jenis' => 'keluar',
            'jumlah' => 250000,
        ])->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Pengajuan penarikan dikirim. Saldo belum dikurangi; menunggu persetujuan admin.');
        $this->assertDatabaseCount('transaksi', 1);
        $this->assertDatabaseCount('transaksi_approval', 1);
    }

    public function test_student_outside_active_class_returns_form_error(): void
    {
        Setting::put('active_class_id', '');
        $this->post('/transaksi', [
            'siswa_id' => $this->siswa->id,
            'tanggal' => '2026-09-11',
            'jenis' => 'masuk',
            'jumlah' => 10000,
        ])->assertSessionHasErrors('siswa_id');
        $this->assertDatabaseCount('transaksi', 0);
    }

    public function test_withdrawal_cannot_exceed_balance(): void
    {
        $this->post('/transaksi', [
            'siswa_id' => $this->siswa->id,
            'tanggal' => '2026-09-11',
            'jenis' => 'keluar',
            'jumlah' => 500000,
        ])->assertSessionHasErrors('jumlah');

        $this->assertDatabaseCount('transaksi', 0);
    }

    public function test_large_withdrawal_is_created_as_pending_approval(): void
    {
        Transaksi::create([
            'siswa_id' => $this->siswa->id,
            'tanggal' => '2026-09-10',
            'jenis' => 'masuk',
            'jumlah' => 2000000,
            'saldo' => 2000000,
        ]);

        $this->post('/transaksi', [
            'siswa_id' => $this->siswa->id,
            'tanggal' => '2026-09-11',
            'jenis' => 'keluar',
            'jumlah' => 1000000,
            'keterangan' => 'Keperluan sekolah',
        ])->assertRedirect('/transaksi')->assertSessionHasNoErrors();

        $approval = TransaksiApproval::firstOrFail();
        $this->assertSame('pending', $approval->status->name);
        $this->assertSame($this->admin->id, $approval->requestedBy->id);
        $this->assertDatabaseCount('transaksi', 1);
    }

    public function test_approval_uses_personal_admin_even_with_an_old_operator_session(): void
    {
        $operator = Admin::create([
            'username' => 'operator',
            'password' => bcrypt('test-password'),
            'nama' => 'Operator',
            'role' => 'operator',
        ]);
        Transaksi::create([
            'siswa_id' => $this->siswa->id,
            'tanggal' => '2026-09-10',
            'jenis' => 'masuk',
            'jumlah' => 2000000,
            'saldo' => 2000000,
        ]);
        $approval = TransaksiApproval::create([
            'siswa_id' => $this->siswa->id,
            'tanggal' => '2026-09-11',
            'jumlah' => 1000000,
            'status_id' => ApprovalStatus::where('name', 'pending')->value('id'),
            'requested_by' => $this->admin->id,
        ]);

        $this->actingAs($operator, 'admin')
            ->patch('/approval/'.$approval->id, ['status' => 'approved'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('transaksi_approval', [
            'id' => $approval->id,
            'status_id' => ApprovalStatus::where('name', 'approved')->value('id'),
            'approved_by' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('transaksi', [
            'siswa_id' => $this->siswa->id,
            'jenis' => 'keluar',
            'jumlah' => 1000000,
            'saldo' => 1000000,
        ]);
    }
}
