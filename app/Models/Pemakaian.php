<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pemakaian extends Model
{
    protected $table = 'pemakaian';

    protected $fillable = ['voucher_id', 'jenis', 'tanggal', 'jumlah', 'keterangan', 'user_id'];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tanggal' => 'date:Y-m-d',
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
