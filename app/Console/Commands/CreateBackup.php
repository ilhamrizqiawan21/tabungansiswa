<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class CreateBackup extends Command
{
    protected $signature = 'tabungan:backup';

    protected $description = 'Membuat arsip database dan lampiran Tabungan Siswa';

    public function handle(BackupService $backups): int
    {
        $name = $backups->create();
        $this->info('Backup tersimpan: '.$name);

        return self::SUCCESS;
    }
}
