<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('telepon', 20)->nullable()->after('kode');
        });

        Schema::table('pemakaian', function (Blueprint $table) {
            // 'pakai' = pemakaian asesi biasa, 'koreksi' = penyesuaian sisa manual
            $table->string('jenis', 10)->default('pakai')->after('voucher_id');
            // Koreksi bisa negatif (mengembalikan sisa), jadi kolom dibuat signed
            $table->integer('jumlah')->change();
        });
    }

    public function down(): void
    {
        Schema::table('pemakaian', function (Blueprint $table) {
            $table->dropColumn('jenis');
            $table->unsignedInteger('jumlah')->change();
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('telepon');
        });
    }
};
