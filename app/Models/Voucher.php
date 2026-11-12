<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Voucher extends Model
{
    protected $fillable = ['klien', 'kode', 'telepon', 'kuota', 'mulai', 'berlaku_bulan', 'berakhir', 'catatan', 'created_by'];

    protected function casts(): array
    {
        return [
            'kuota' => 'integer',
            'berlaku_bulan' => 'integer',
            'mulai' => 'date:Y-m-d',
            'berakhir' => 'date:Y-m-d',
        ];
    }

    public function pemakaian(): HasMany
    {
        return $this->hasMany(Pemakaian::class);
    }

    /** Normalisasi nomor ke format internasional Indonesia (08xx / 8xx / +62 → 62xx). */
    public static function normalisasiTelepon(?string $no): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $no);
        if ($d === '') {
            return null;
        }
        if (str_starts_with($d, '0')) {
            $d = '62'.substr($d, 1);
        } elseif (str_starts_with($d, '8')) {
            $d = '62'.$d;
        }

        return $d;
    }

    /** Tanggal terakhir masa berlaku: mulai + N bulan - 1 hari. */
    public static function hitungBerakhir(string $mulai, int $bulan): string
    {
        return Carbon::parse($mulai)->addMonthsNoOverflow($bulan)->subDay()->toDateString();
    }
}
