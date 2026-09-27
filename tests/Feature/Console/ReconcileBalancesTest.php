<?php

namespace Tests\Feature\Console;

use App\Models\Siswa;
use App\Models\Transaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconcileBalancesTest extends TestCase
{
    use RefreshDatabase;

    public function test_succeeds_when_snapshots_match_the_ledger_sum(): void
    {
        $siswa = Siswa::factory()->create();
        Transaksi::factory()->for($siswa)->setoran(10000)->create();
        Transaksi::factory()->for($siswa)->setoran(5000)->create(['saldo' => 15000]);

        $this->artisan('tabungan:rekonsiliasi')->assertSuccessful();
    }

    public function test_fails_and_lists_student_whose_snapshot_drifted(): void
    {
        $siswa = Siswa::factory()->create(['nis' => '00777']);
        Transaksi::factory()->for($siswa)->setoran(10000)->create();
        Transaksi::factory()->for($siswa)->setoran(5000)->create(['saldo' => 99000]);

        $this->artisan('tabungan:rekonsiliasi')
            ->expectsOutputToContain('00777')
            ->assertFailed();
    }
}
