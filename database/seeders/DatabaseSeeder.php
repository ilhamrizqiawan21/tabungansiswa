<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = env('SEED_ADMIN_PASSWORD') ?: Str::random(24);
        DB::table('admin')->updateOrInsert(['username' => env('SEED_ADMIN_USERNAME', 'admin')], ['password' => Hash::make($password), 'nama' => env('SEED_ADMIN_NAME', 'Administrator'), 'role' => 'admin', 'created_at' => now(), 'updated_at' => now()]);
        foreach (['pending', 'approved', 'rejected', 'revised'] as $name) DB::table('approval_status')->updateOrInsert(['name' => $name], ['created_at' => now(), 'updated_at' => now()]);
        if (!env('SEED_ADMIN_PASSWORD')) $this->command->warn("Password admin sementara: {$password}");
        $this->command->info('Akun admin dan status approval berhasil di-seed.');
    }
}
