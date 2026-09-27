<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ApprovalStatus;
use App\Models\Siswa;
use App\Models\Transaksi;
use App\Models\TransaksiApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ApprovalControllerTest extends TestCase
{
    use RefreshDatabase;

    private function pendingWithdrawal(Siswa $siswa, float $jumlah): TransaksiApproval
    {
        foreach (['pending', 'approved', 'rejected'] as $name) {
            ApprovalStatus::firstOrCreate(['name' => $name]);
        }
        $admin = Admin::firstOrCreate(['username' => 'owner'], ['password' => bcrypt('x'), 'nama' => 'Pengelola', 'role' => 'admin']);

        return TransaksiApproval::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-09-20',
            'jumlah' => $jumlah,
            'status_id' => ApprovalStatus::where('name', 'pending')->value('id'),
            'requested_by' => $admin->id,
        ]);
    }

    public function test_approving_same_request_twice_books_only_one_withdrawal(): void
    {
        $siswa = Siswa::factory()->create();
        Transaksi::factory()->for($siswa)->setoran(2000000)->create();
        $approval = $this->pendingWithdrawal($siswa, 1000000);

        $this->patch('/approval/'.$approval->id, ['status' => 'approved'])->assertRedirect();
        $this->patch('/approval/'.$approval->id, ['status' => 'approved'])->assertStatus(409);

        $this->assertSame(1, Transaksi::where('jenis', 'keluar')->count());
        $this->assertSame(1000000.0, $siswa->currentBalance());
    }

    public function test_rejection_requires_reason(): void
    {
        $approval = $this->pendingWithdrawal(Siswa::factory()->create(), 1000000);

        $this->patch('/approval/'.$approval->id, ['status' => 'rejected'])
            ->assertSessionHasErrors(['reason' => 'Alasan wajib diisi bila status adalah rejected.']);
    }

    public function test_pending_list_shows_total_and_current_student_balance(): void
    {
        $siswa = Siswa::factory()->create();
        Transaksi::factory()->for($siswa)->setoran(1500000)->create();
        $this->pendingWithdrawal($siswa, 1000000);

        $this->get('/approval')->assertInertia(fn (Assert $page) => $page
            ->component('Approvals/Index')
            ->where('status', 'pending')
            ->where('items.total', 1)
            ->where('items.data.0.saldoSiswa', 1500000));
    }

    public function test_history_tab_lists_rejected_requests_with_reason(): void
    {
        $approval = $this->pendingWithdrawal(Siswa::factory()->create(), 1000000);
        $this->patch('/approval/'.$approval->id, ['status' => 'rejected', 'reason' => 'Tidak ada surat orang tua']);

        $this->get('/approval?status=rejected')->assertInertia(fn (Assert $page) => $page
            ->where('items.total', 1)
            ->where('items.data.0.rejectionReason', 'Tidak ada surat orang tua'));
        $this->get('/approval')->assertInertia(fn (Assert $page) => $page->where('items.total', 0));
    }
}
