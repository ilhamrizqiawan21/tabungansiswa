<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class CreateBackup extends Command
{
    protected $signature = 'tabungan:backup {--prune : Hapus arsip lama melebihi batas retensi (config tabungan.backup_retention)}';

    protected $description = 'Membuat arsip database dan lampiran Tabungan Siswa';

    public function handle(BackupService $backups): int
    {
        $name = $backups->create();
        $this->info('Backup tersimpan: '.$name);

        if ($this->option('prune')) {
            $removed = $backups->prune((int) config('tabungan.backup_retention'));
            $this->info("Arsip lama dihapus: {$removed}");
        }

        return self::SUCCESS;
    }
}
