<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\TahunPelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tingkat = fake()->randomElement(['VII', 'VIII', 'IX']);

        return [
            'nama_kelas' => $tingkat.'-'.fake()->unique()->bothify('?#'),
            'tingkat' => $tingkat,
            'jurusan' => null,
            'tahun_pelajaran_id' => TahunPelajaran::factory(),
            'wali_kelas' => fake()->name(),
        ];
    }
}
