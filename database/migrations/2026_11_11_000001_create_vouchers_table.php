<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('klien', 150);
            $table->string('kode', 80)->nullable();
            $table->unsignedInteger('kuota');
            $table->date('mulai');
            $table->unsignedSmallInteger('berlaku_bulan');
            $table->date('berakhir');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('berakhir');
        });

        Schema::create('pemakaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedInteger('jumlah');
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['voucher_id', 'tanggal']);
        });

        // Penanda revisi data: naik setiap ada perubahan, dipakai untuk sinkronisasi realtime.
        Schema::create('app_state', function (Blueprint $table) {
            $table->string('key', 50)->primary();
            $table->unsignedBigInteger('value')->default(0);
        });

        DB::table('app_state')->insert(['key' => 'revision', 'value' => 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_state');
        Schema::dropIfExists('pemakaian');
        Schema::dropIfExists('vouchers');
    }
};
