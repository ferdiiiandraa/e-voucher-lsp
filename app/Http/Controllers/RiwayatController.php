<?php

namespace App\Http\Controllers;

use App\Models\Pemakaian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Riwayat pemakaian lengkap untuk voucher tertentu. Dipanggil saat voucher dibuka
 * di halaman Kelola dan saat ekspor Excel.
 */
class RiwayatController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->take(1000)
            ->values();

        if ($ids->isEmpty()) {
            return response()->json(['logs' => (object) []]);
        }

        $logs = Pemakaian::query()
            ->with(['user:id,name'])
            ->whereIn('voucher_id', $ids)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->groupBy('voucher_id')
            ->map(fn ($rows) => $rows->map(fn (Pemakaian $p) => [
                'id' => $p->id,
                'jenis' => $p->jenis,
                'tanggal' => $p->tanggal->toDateString(),
                'jumlah' => $p->jumlah,
                'keterangan' => $p->keterangan,
                'oleh' => $p->user?->name,
            ])->values());

        return response()->json(['logs' => (object) $logs->all()]);
    }
}
