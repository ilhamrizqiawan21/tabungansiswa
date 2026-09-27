<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_approval', function (Blueprint $table) {
            $table->foreignId('siswa_id')
                ->nullable()
                ->after('id')
                ->constrained('siswa')
                ->restrictOnDelete();

            $table->date('tanggal')
                ->nullable()
                ->after('siswa_id');

            $table->decimal('jumlah', 15, 2)
                ->nullable()
                ->after('tanggal');

            $table->string('keterangan')
                ->nullable()
                ->after('jumlah');
        });

        Schema::table('transaksi_approval', function (Blueprint $table) {
            $table->dropForeign(['transaksi_id']);
            $table->dropUnique(['transaksi_id']);

            $table->unsignedBigInteger('transaksi_id')
                ->nullable()
                ->change();
            $table->unique('transaksi_id');

            $table->foreign('transaksi_id')
                ->references('id')
                ->on('transaksi')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $pending = DB::table('transaksi_approval')->whereNull('transaksi_id')->count();

        if ($pending > 0) {
            throw new RuntimeException(
                "Cannot roll back: {$pending} row(s) in transaksi_approval have no transaksi_id yet ".
                '(pending approval requests). Resolve or manually remove them before rolling back this migration.'
            );
        }

        Schema::table('transaksi_approval', function (Blueprint $table) {
            $table->dropForeign(['transaksi_id']);
            $table->dropUnique(['transaksi_id']);

            $table->unsignedBigInteger('transaksi_id')
                ->nullable(false)
                ->change();

            $table->unique('transaksi_id');

            $table->foreign('transaksi_id')
                ->references('id')
                ->on('transaksi')
                ->cascadeOnDelete();
        });

        Schema::table('transaksi_approval', function (Blueprint $table) {
            $table->dropForeign(['siswa_id']);

            $table->dropColumn([
                'siswa_id',
                'tanggal',
                'jumlah',
                'keterangan',
            ]);
        });
    }
};
