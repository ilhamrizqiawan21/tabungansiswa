<?php

namespace App\Console\Commands;

use App\Models\Siswa;
use App\Models\Transaksi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tabungan:rekonsiliasi {--siswa= : Hanya periksa satu ID siswa}')]
#[Description('Bandingkan snapshot saldo transaksi terakhir dengan jumlah SUM() transaksi per siswa')]
class ReconcileBalances extends Command
{
    public function handle(): int
    {
        $mismatches = [];
        $checked = 0;

        Siswa::withTrashed()
            ->when($this->option('siswa'), fn ($q, $id) => $q->whereKey($id))
            ->whereHas('transaksi')
            ->orderBy('id')
            ->chunkById(200, function ($students) use (&$mismatches, &$checked) {
                foreach ($students as $siswa) {
                    $checked++;

                    $expected = (float) Transaksi::where('siswa_id', $siswa->id)->effective()
                        ->selectRaw(Transaksi::SALDO_EXPRESSION.' as saldo')->value('saldo');
                    $snapshot = (float) Transaksi::where('siswa_id', $siswa->id)->effective()
                        ->latest('id')->value('saldo');

                    if (abs($expected - $snapshot) >= 0.01) {
                        $mismatches[] = [$siswa->id, $siswa->nis, $siswa->nama, $this->money($snapshot), $this->money($expected), $this->money($snapshot - $expected)];
                    }
                }
            });

        if ($mismatches === []) {
            $this->components->info("Saldo {$checked} siswa konsisten.");

            return self::SUCCESS;
        }

        $this->components->error(count($mismatches)." dari {$checked} siswa memiliki snapshot saldo yang berbeda.");
        $this->table(['ID', 'NIS', 'Nama', 'Snapshot terakhir', 'SUM transaksi', 'Selisih'], $mismatches);
        $this->line('Saldo yang ditampilkan aplikasi memakai SUM transaksi; snapshot hanya dipakai pada riwayat/laporan.');

        return self::FAILURE;
    }

    private function money(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }
}
