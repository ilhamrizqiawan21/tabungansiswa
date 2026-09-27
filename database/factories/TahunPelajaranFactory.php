<?php

namespace Database\Factories;

use App\Models\TahunPelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TahunPelajaran>
 */
class TahunPelajaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->unique()->numberBetween(2000, 2099);

        return [
            'tahun' => $start.'/'.($start + 1),
            'semester' => fake()->randomElement(['ganjil', 'genap']),
            'status' => 'nonaktif',
        ];
    }

    public function aktif(): static
    {
        return $this->state(fn (): array => ['status' => 'aktif']);
    }
}
