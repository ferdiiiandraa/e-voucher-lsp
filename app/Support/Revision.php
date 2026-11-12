<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Nomor revisi data global. Setiap perubahan voucher/pemakaian menaikkan angka ini,
 * sehingga browser yang sedang membuka aplikasi tahu kapan harus mengambil data baru.
 */
class Revision
{
    public static function current(): int
    {
        return (int) DB::table('app_state')->where('key', 'revision')->value('value');
    }

    public static function bump(): void
    {
        DB::table('app_state')->where('key', 'revision')->increment('value');
    }
}
