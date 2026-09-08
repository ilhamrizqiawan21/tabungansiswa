<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('admin', fn(Blueprint $t) => [$t->id(), $t->string('username',50)->unique(), $t->string('password'), $t->string('nama',100), $t->enum('role',['admin','operator'])->default('admin'), $t->timestamps()]);
        Schema::create('tahun_pelajaran', fn(Blueprint $t) => [$t->id(), $t->string('tahun',9), $t->enum('semester',['ganjil','genap']), $t->enum('status',['aktif','nonaktif'])->default('nonaktif'), $t->timestamps(), $t->unique(['tahun','semester'])]);
        Schema::create('kelas', fn(Blueprint $t) => [$t->id(), $t->string('nama_kelas',50), $t->string('tingkat',10), $t->string('jurusan',50)->nullable(), $t->foreignId('tahun_pelajaran_id')->nullable()->constrained('tahun_pelajaran')->nullOnDelete(), $t->string('wali_kelas',100)->nullable(), $t->timestamps(), $t->unique(['nama_kelas','tahun_pelajaran_id'])]);
        Schema::create('siswa', fn(Blueprint $t) => [$t->id(), $t->string('nis',20)->unique(), $t->string('nama',100), $t->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete(), $t->string('kontak',25)->nullable(), $t->timestamps()]);
        Schema::create('transaksi', fn(Blueprint $t) => [$t->id(), $t->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete(), $t->date('tanggal'), $t->enum('jenis',['masuk','keluar']), $t->decimal('jumlah',15,2), $t->string('keterangan')->nullable(), $t->decimal('saldo',15,2)->default(0), $t->boolean('approval_required')->default(false), $t->timestamps(), $t->index(['siswa_id','tanggal','id'])]);
        Schema::create('approval_status', fn(Blueprint $t) => [$t->id(), $t->string('name',50)->unique(), $t->string('description')->nullable(), $t->timestamps()]);
        Schema::create('transaksi_approval', fn(Blueprint $t) => [$t->id(), $t->foreignId('transaksi_id')->unique()->constrained('transaksi')->cascadeOnDelete(), $t->foreignId('status_id')->constrained('approval_status'), $t->foreignId('requested_by')->constrained('admin'), $t->foreignId('approved_by')->nullable()->constrained('admin')->nullOnDelete(), $t->text('rejection_reason')->nullable(), $t->timestamp('request_date')->useCurrent(), $t->timestamp('approval_date')->nullable()]);
        Schema::create('audit_log', fn(Blueprint $t) => [$t->id(), $t->foreignId('admin_id')->nullable()->constrained('admin')->nullOnDelete(), $t->string('table_name',100), $t->unsignedBigInteger('record_id')->nullable(), $t->enum('action',['CREATE','UPDATE','DELETE']), $t->json('old_values')->nullable(), $t->json('new_values')->nullable(), $t->text('description')->nullable(), $t->string('ip_address',45)->nullable(), $t->string('user_agent',500)->nullable(), $t->timestamp('created_at')->useCurrent(), $t->index(['table_name','action']), $t->index('created_at')]);
    }
    public function down(): void { foreach (['audit_log','transaksi_approval','approval_status','transaksi','siswa','kelas','tahun_pelajaran','admin'] as $table) Schema::dropIfExists($table); }
};
