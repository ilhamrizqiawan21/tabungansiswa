<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Siswa>
 */
class SiswaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nis' => fake()->unique()->numerify('00####'),
            'nama' => fake()->name(),
            'kelas_id' => Kelas::factory(),
            'kontak' => fake()->optional()->numerify('08##########'),
            'status' => 'aktif',
        ];
    }

    public function lulus(): static
    {
        return $this->state(fn (): array => ['status' => 'lulus']);
    }
}
