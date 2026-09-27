<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupService
{
    public function create(): string
    {
        $disk = Storage::disk('backups');
        $disk->makeDirectory('');
        $name = 'tabungan-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(8)).'.zip';
        $work = $disk->path('.'.Str::uuid());
        if (! mkdir($work, 0700, true)) {
            throw new RuntimeException('Folder sementara backup tidak dapat dibuat.');
        }
        $zip = new ZipArchive;
        $opened = false;

        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();
            $databaseFile = $driver === 'sqlite' ? 'database.sqlite' : 'database.sql';
            $databasePath = $work.'/'.$databaseFile;

            if ($driver === 'sqlite') {
                $connection->getPdo()->exec('VACUUM INTO '.$connection->getPdo()->quote($databasePath));
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                $binary = (new ExecutableFinder)->find('mariadb-dump') ?? (new ExecutableFinder)->find('mysqldump');
                if (! $binary) {
                    throw new RuntimeException('Program mariadb-dump atau mysqldump belum tersedia.');
                }
                $config = $connection->getConfig();
                $arguments = [$binary, '--single-transaction', '--quick', '--skip-lock-tables', '--no-tablespaces', '--result-file='.$databasePath];
                if (! empty($config['unix_socket'])) {
                    $arguments[] = '--socket='.$config['unix_socket'];
                } else {
                    $arguments[] = '--host='.($config['host'] ?? '127.0.0.1');
                    $arguments[] = '--port='.($config['port'] ?? 3306);
                }
                $arguments[] = '--user='.$config['username'];
                $arguments[] = '--';
                $arguments[] = $config['database'];
                $process = new Process($arguments, null, ['MYSQL_PWD' => $config['password'] ?? ''], null, 120);
                $process->run();
                if (! $process->isSuccessful()) {
                    throw new RuntimeException('Salinan database gagal dibuat. Periksa koneksi database dan izin pengguna backup.');
                }
            } else {
                throw new RuntimeException('Backup mendukung database MySQL, MariaDB, dan SQLite.');
            }

            if (! is_file($databasePath) || filesize($databasePath) === 0) {
                throw new RuntimeException('Salinan database kosong.');
            }
            if ($zip->open($work.'/backup.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Arsip backup tidak dapat dibuat.');
            }
            $opened = true;
            if (! $zip->addFile($databasePath, $databaseFile)) {
                throw new RuntimeException('Database tidak dapat dimasukkan ke arsip.');
            }
            foreach (Storage::disk('public')->allFiles() as $file) {
                if (! $zip->addFile(Storage::disk('public')->path($file), 'uploads/'.$file)) {
                    throw new RuntimeException('Lampiran tidak dapat dimasukkan ke arsip.');
                }
            }
            $zip->addFromString('manifest.json', json_encode(['application' => 'Tabungan Siswa', 'created_at' => now()->toIso8601String(), 'database_driver' => $driver, 'database_file' => $databaseFile, 'database_sha256' => hash_file('sha256', $databasePath)], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $zip->addFromString('PETUNJUK.txt', "BACKUP TABUNGAN SISWA\n\nArsip berisi database lengkap dan folder uploads (logo/lampiran).\nKode aplikasi dan file .env tidak disertakan.\n\nPemulihan: hentikan aplikasi, buat salinan data yang sekarang, lalu ekstrak arsip ke folder sementara.\nMySQL/MariaDB: impor database.sql ke database kosong melalui klien database.\nSQLite: salin database.sqlite ke lokasi database yang diatur dalam .env.\nSalin isi uploads ke storage/app/public. Gunakan versi aplikasi yang sama dan jalankan php artisan storage:link.\nVerifikasi jumlah siswa, transaksi, serta saldo sebelum digunakan kembali.\n\nSimpan arsip tambahan di flashdisk atau media lain agar tetap ada jika laptop rusak.\n");
            if (! $zip->close()) {
                throw new RuntimeException('Arsip backup gagal diselesaikan.');
            }
            $opened = false;
            if (! rename($work.'/backup.zip', $disk->path($name))) {
                throw new RuntimeException('Arsip backup gagal disimpan.');
            }
            chmod($disk->path($name), 0600);

            return $name;
        } finally {
            if ($opened) {
                $zip->close();
            }
            foreach (glob($work.'/*') ?: [] as $temporary) {
                unlink($temporary);
            }
            rmdir($work);
        }
    }

    /**
     * Keep only the newest `$keep` archives. Returns how many were deleted.
     */
    public function prune(int $keep): int
    {
        if ($keep <= 0) {
            return 0;
        }

        $old = array_slice($this->listing(), $keep);
        foreach ($old as $item) {
            Storage::disk('backups')->delete($item['name']);
        }

        return count($old);
    }

    /**
     * @return array<int, array{name: string, size: int, created_at: string}>
     */
    public function listing(): array
    {
        $disk = Storage::disk('backups');

        return collect($disk->files())->filter(fn ($name) => preg_match('/^tabungan-[A-Za-z0-9-]+\.zip$/', $name))
            ->map(fn ($name) => ['name' => $name, 'size' => $disk->size($name), 'created_at' => date(DATE_ATOM, $disk->lastModified($name))])
            ->sortByDesc('created_at')->values()->all();
    }
}
