<?php

namespace App\Providers;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\TahunPelajaran;
use App\Models\Transaksi;
use App\Observers\AuditableObserver;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([Siswa::class, Kelas::class, TahunPelajaran::class, Transaksi::class] as $model) $model::observe(AuditableObserver::class);
    }
}
