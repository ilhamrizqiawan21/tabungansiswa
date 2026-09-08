<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Auth\AdminAuthController;

Route::get('/', fn () => redirect()->route('dashboard'));
Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
});
Route::middleware('auth:admin')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::prefix('master')->name('master.')->group(function () {
        Route::get('/tahun-pelajaran', [MasterDataController::class, 'tahun'])->name('tahun');
        Route::post('/tahun-pelajaran', [MasterDataController::class, 'storeTahun'])->name('tahun.store');
        Route::patch('/tahun-pelajaran/{tahun}/aktifkan', [MasterDataController::class, 'activateTahun'])->name('tahun.activate');
        Route::delete('/tahun-pelajaran/{tahun}', [MasterDataController::class, 'destroyTahun'])->name('tahun.destroy');
        Route::get('/kelas', [MasterDataController::class, 'kelas'])->name('kelas');
        Route::get('/kelas/create', [MasterDataController::class, 'kelasCreate'])->name('kelas.create');
        Route::post('/kelas', [MasterDataController::class, 'kelasStore'])->name('kelas.store');
        Route::get('/kelas/{kelas}/edit', [MasterDataController::class, 'kelasEdit'])->name('kelas.edit');
        Route::patch('/kelas/{kelas}', [MasterDataController::class, 'kelasUpdate'])->name('kelas.update');
        Route::delete('/kelas/{kelas}', [MasterDataController::class, 'kelasDestroy'])->name('kelas.destroy');
        Route::get('/siswa', [MasterDataController::class, 'siswa'])->name('siswa');
        Route::post('/siswa', [MasterDataController::class, 'siswaStore'])->name('siswa.store');
        Route::patch('/siswa/{siswa}', [MasterDataController::class, 'siswaUpdate'])->name('siswa.update');
        Route::delete('/siswa/{siswa}', [MasterDataController::class, 'siswaDestroy'])->name('siswa.destroy');
        Route::post('/siswa/import', [MasterDataController::class, 'siswaImport'])->name('siswa.import');
        Route::get('/siswa/template', [MasterDataController::class, 'siswaTemplate'])->name('siswa.template');
    });
    Route::resource('transaksi', TransactionController::class)->only(['index', 'create', 'store'])->names('transactions');
    Route::get('/approval', [ApprovalController::class, 'index'])->middleware('admin.role')->name('approval.index');
    Route::patch('/approval/{approval}', [ApprovalController::class, 'update'])->middleware('admin.role')->name('approval.update');
    Route::get('/audit-log', [AuditLogController::class, 'index'])->middleware('admin.role')->name('audit.index');
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/laporan/export-xlsx', [ReportController::class, 'exportXlsx'])->name('reports.export.xlsx');
    Route::get('/laporan/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('/laporan/cetak', [ReportController::class, 'print'])->name('reports.print');
    Route::get('/pengaturan', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/pengaturan', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
});
