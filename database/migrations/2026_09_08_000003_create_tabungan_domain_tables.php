<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('nama', 100);
            $table->enum('role', ['admin', 'operator'])->default('admin');
            $table->timestamps();
        });

        Schema::create('tahun_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->string('tahun', 9);
            $table->enum('semester', ['ganjil', 'genap']);
            $table->enum('status', ['aktif', 'nonaktif'])->default('nonaktif');
            $table->timestamps();
            $table->unique(['tahun', 'semester']);
        });

        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kelas', 50);
            $table->string('tingkat', 10);
            $table->string('jurusan', 50)->nullable();
            $table->foreignId('tahun_pelajaran_id')->nullable()->constrained('tahun_pelajaran')->nullOnDelete();
            $table->string('wali_kelas', 100)->nullable();
            $table->timestamps();
            $table->unique(['nama_kelas', 'tahun_pelajaran_id']);
        });

        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 20)->unique();
            $table->string('nama', 100);
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->string('kontak', 25)->nullable();
            $table->timestamps();
        });

        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->decimal('jumlah', 15, 2);
            $table->string('keterangan')->nullable();
            $table->decimal('saldo', 15, 2)->default(0);
            $table->boolean('approval_required')->default(false);
            $table->timestamps();
            $table->index(['siswa_id', 'tanggal', 'id']);
        });

        Schema::create('approval_status', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('transaksi_approval', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->unique()->constrained('transaksi')->cascadeOnDelete();
            $table->foreignId('status_id')->constrained('approval_status');
            $table->foreignId('requested_by')->constrained('admin');
            $table->foreignId('approved_by')->nullable()->constrained('admin')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('request_date')->useCurrent();
            $table->timestamp('approval_date')->nullable();
        });

        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admin')->nullOnDelete();
            $table->string('table_name', 100);
            $table->unsignedBigInteger('record_id')->nullable();
            $table->enum('action', ['CREATE', 'UPDATE', 'DELETE']);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['table_name', 'action']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        foreach (['audit_log', 'transaksi_approval', 'approval_status', 'transaksi', 'siswa', 'kelas', 'tahun_pelajaran', 'admin'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
