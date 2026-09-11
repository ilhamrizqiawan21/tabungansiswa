<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    public function index(BackupService $backups): Response
    {
        return Inertia::render('Backups/Index', ['items' => $backups->listing()]);
    }

    public function store(BackupService $backups): RedirectResponse
    {
        try {
            $backups->create();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Backup gagal dibuat. Pastikan database aktif dan ruang penyimpanan tersedia, lalu coba lagi.');
        }

        return to_route('backups.index')->with('success', 'Backup berhasil dibuat. Unduh salinannya untuk disimpan di media lain.');
    }

    public function download(string $filename): StreamedResponse
    {
        abort_unless(preg_match('/^tabungan-[A-Za-z0-9-]+\.zip$/', $filename) && Storage::disk('backups')->exists($filename), 404);

        return Storage::disk('backups')->download($filename);
    }
}
