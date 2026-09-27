<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keep financial history intact: students and transactions are soft deleted,
     * and deleting a student can no longer cascade away its ledger.
     */
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->enum('status', ['aktif', 'lulus', 'keluar'])->default('aktif')->after('kontak');
            $table->softDeletes();
            $table->index('nama');
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropForeign(['siswa_id']);
            $table->foreign('siswa_id')->references('id')->on('siswa')->restrictOnDelete();

            $table->foreignId('reversal_of_id')->nullable()->after('approval_required')->unique()->constrained('transaksi')->restrictOnDelete();
            $table->softDeletes();
        });

        Schema::table('transaksi_approval', function (Blueprint $table) {
            $table->dropForeign(['transaksi_id']);
            $table->foreign('transaksi_id')->references('id')->on('transaksi')->nullOnDelete();

            $table->dropForeign(['requested_by']);
            $table->foreign('requested_by')->references('id')->on('admin')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_approval', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->foreign('requested_by')->references('id')->on('admin');

            $table->dropForeign(['transaksi_id']);
            $table->foreign('transaksi_id')->references('id')->on('transaksi')->cascadeOnDelete();
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['reversal_of_id']);
            $table->dropUnique(['reversal_of_id']);
            $table->dropColumn('reversal_of_id');

            $table->dropForeign(['siswa_id']);
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropIndex(['nama']);
            $table->dropSoftDeletes();
            $table->dropColumn('status');
        });
    }
};
