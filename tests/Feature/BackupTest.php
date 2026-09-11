<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use PDO;
use Tests\TestCase;
use ZipArchive;

class BackupTest extends TestCase
{
    use DatabaseMigrations;

    public function test_backup_contains_restorable_database_uploads_and_instructions(): void
    {
        Storage::fake('backups');
        Storage::fake('public');
        Storage::disk('public')->put('school-logos/logo.png', 'school image');
        Siswa::create(['nis' => '00123', 'nama' => 'Budi']);

        $this->post('/backup')->assertRedirect('/backup')->assertSessionHas('success');

        $files = Storage::disk('backups')->files();
        $this->assertCount(1, $files);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('backups')->path($files[0])));
        $this->assertSame('school image', $zip->getFromName('uploads/school-logos/logo.png'));
        $this->assertNotFalse($zip->getFromName('PETUNJUK.txt'));
        $manifest = json_decode($zip->getFromName('manifest.json'), true);
        $database = $zip->getFromName('database.sqlite');
        $this->assertSame(hash('sha256', $database), $manifest['database_sha256']);
        $this->assertFalse($zip->locateName('.env'));
        $zip->close();
        $snapshot = tempnam(sys_get_temp_dir(), 'tabungan-restore-test-');
        try {
            file_put_contents($snapshot, $database);
            $restored = new PDO('sqlite:'.$snapshot);
            $this->assertSame('Budi', $restored->query("SELECT nama FROM siswa WHERE nis = '00123'")->fetchColumn());
        } finally {
            unset($restored);
            unlink($snapshot);
        }
        $this->get('/backup/'.$files[0])->assertDownload($files[0]);
        $this->get('/backup')->assertSee($files[0]);
    }

    public function test_backup_command_creates_archive(): void
    {
        Storage::fake('backups');
        Storage::fake('public');

        $this->artisan('tabungan:backup')->assertSuccessful();

        $this->assertCount(1, app(BackupService::class)->listing());
    }

    public function test_failed_snapshot_does_not_leave_downloadable_partial_backup(): void
    {
        Storage::fake('backups');
        Storage::fake('public');
        Exceptions::fake();
        DB::beginTransaction();
        try {
            $this->from('/backup')->post('/backup')->assertRedirect('/backup')->assertSessionHas('error');
        } finally {
            DB::rollBack();
        }

        $this->assertSame([], Storage::disk('backups')->allFiles());
        $this->assertSame([], app(BackupService::class)->listing());
    }

    public function test_backup_download_rejects_unknown_and_non_archive_files(): void
    {
        Storage::fake('backups');
        Storage::disk('backups')->put('secret.txt', 'private');

        $this->get('/backup/secret.txt')->assertNotFound();
        $this->get('/backup/tabungan-missing.zip')->assertNotFound();
    }
}
