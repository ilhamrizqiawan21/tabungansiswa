<?php

namespace Tests\Feature\Services;

use App\Models\Admin;
use App\Models\ApprovalStatus;
use App\Models\Siswa;
use App\Models\Transaksi;
use App\Models\TransaksiApproval;
use App\Services\TransactionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): TransactionService
    {
        return app(TransactionService::class);
    }

    private function admin(): Admin
    {
        return Admin::create(['username' => 'svc', 'password' => bcrypt('x'), 'nama' => 'Admin', 'role' => 'admin']);
    }

    public function test_deposit_stores_running_balance_snapshot(): void
    {
        $siswa = Siswa::factory()->create();
        Transaksi::factory()->for($siswa)->setoran(20000)->create();

        $result = $this->service()->create(['siswa_id' => $siswa->id, 'tanggal' => '2026-09-20', 'jenis' => 'masuk', 'jumlah' => 5000, 'requested_by' => $this->admin()->id]);

        $this->assertInstanceOf(Transaksi::class, $result);
        $this->assertSame('25000.00', $result->saldo);
    }

    public function test_withdrawal_above_threshold_creates_pending_request_without_ledger_row(): void
    {
        config(['tabungan.approval_threshold' => 10000]);
        ApprovalStatus::create(['name' => 'pending']);
        $siswa = Siswa::factory()->create();
        Transaksi::factory()->for($siswa)->setoran(50000)->create();

        $result = $this->service()->create(['siswa_id' => $siswa->id, 'tanggal' => '2026-09-20', 'jenis' => 'keluar', 'jumlah' => 10000, 'requested_by' => $this->admin()->id]);

        $this->assertInstanceOf(TransaksiApproval::class, $result);
        $this->assertSame(1, Transaksi::count());
    }

    public function test_rejects_new_transaction_for_graduated_student(): void
    {
        $siswa = Siswa::factory()->lulus()->create();

        $this->expectException(ValidationException::class);

        $this->service()->create(['siswa_id' => $siswa->id, 'tanggal' => '2026-09-20', 'jenis' => 'masuk', 'jumlah' => 5000, 'requested_by' => $this->admin()->id]);
    }

    public function test_reversing_deposit_books_opposite_entry_and_keeps_original(): void
    {
        $siswa = Siswa::factory()->create();
        $deposit = Transaksi::factory()->for($siswa)->setoran(30000)->create();

        $reversal = $this->service()->reverse($deposit, 'Salah input nominal');

        $this->assertSame('keluar', $reversal->jenis);
        $this->assertSame($deposit->id, $reversal->reversal_of_id);
        $this->assertSame('0.00', $reversal->saldo);
        $this->assertStringContainsString('Salah input nominal', $reversal->keterangan);
        $this->assertModelExists($deposit);
        $this->assertDatabaseHas('audit_log', ['table_name' => 'transaksi', 'record_id' => $reversal->id, 'description' => sprintf('Koreksi (pembalikan) transaksi TS-%06d', $deposit->id)]);
    }

    public function test_transaction_cannot_be_reversed_twice(): void
    {
        $deposit = Transaksi::factory()->setoran(30000)->create();
        $this->service()->reverse($deposit, 'Salah input pertama');

        try {
            $this->service()->reverse($deposit, 'Salah input kedua');
            $this->fail('Second reversal should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame('Transaksi ini sudah pernah dikoreksi.', $exception->errors()['alasan'][0]);
        }

        $this->assertSame(2, Transaksi::count());
    }

    public function test_reversal_row_cannot_be_reversed(): void
    {
        $reversal = $this->service()->reverse(Transaksi::factory()->setoran(30000)->create(), 'Salah input nominal');

        $this->expectException(ValidationException::class);

        $this->service()->reverse($reversal, 'Batalkan koreksi');
    }

    public function test_reversing_deposit_already_spent_is_rejected(): void
    {
        $siswa = Siswa::factory()->create();
        $deposit = Transaksi::factory()->for($siswa)->setoran(30000)->create();
        Transaksi::factory()->for($siswa)->penarikan(20000)->create(['saldo' => 10000]);

        $this->expectException(ValidationException::class);

        $this->service()->reverse($deposit, 'Salah input nominal');
    }

    public function test_withdraw_all_empties_balance_and_skips_zero_balance(): void
    {
        $siswa = Siswa::factory()->create();
        Transaksi::factory()->for($siswa)->setoran(45000)->create();

        $payout = $this->service()->withdrawAll($siswa, 'Lulus');

        $this->assertSame('45000.00', $payout->jumlah);
        $this->assertSame(0.0, $siswa->currentBalance());
        $this->assertNull($this->service()->withdrawAll($siswa, 'Lulus'));
    }

    public function test_student_with_ledger_cannot_be_hard_deleted(): void
    {
        $siswa = Siswa::factory()->create();
        Transaksi::factory()->for($siswa)->create();

        $this->expectException(QueryException::class);

        $siswa->forceDelete();
    }
}
