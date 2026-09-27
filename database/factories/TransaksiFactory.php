<?php

namespace Database\Factories;

use App\Models\Siswa;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Note: `saldo` is a running-balance snapshot; factories do not recompute it.
 * Pass an explicit `saldo` when a test depends on it.
 *
 * @extends Factory<Transaksi>
 */
class TransaksiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jumlah = fake()->numberBetween(1, 50) * 1000;

        return [
            'siswa_id' => Siswa::factory(),
            'tanggal' => fake()->dateTimeBetween('-3 months')->format('Y-m-d'),
            'jenis' => 'masuk',
            'jumlah' => $jumlah,
            'keterangan' => null,
            'saldo' => $jumlah,
            'approval_required' => false,
        ];
    }

    public function setoran(float $jumlah): static
    {
        return $this->state(fn (): array => ['jenis' => 'masuk', 'jumlah' => $jumlah, 'saldo' => $jumlah]);
    }

    public function penarikan(float $jumlah): static
    {
        return $this->state(fn (): array => ['jenis' => 'keluar', 'jumlah' => $jumlah]);
    }
}
