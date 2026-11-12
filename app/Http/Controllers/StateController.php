<?php

namespace App\Http\Controllers;

use App\Models\Pemakaian;
use App\Models\Voucher;
use App\Support\Revision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint sinkronisasi. Browser memanggilnya berkala dengan nomor revisi terakhir yang
 * dimilikinya; data hanya dikirim bila ada perubahan.
 *
 * Agar ringan, yang dikirim hanya RINGKASAN tiap voucher (total terpakai & jumlah catatan)
 * plus beberapa aktivitas terbaru. Riwayat lengkap diambil terpisah lewat RiwayatController
 * hanya saat voucher dibuka atau diekspor.
 */
class StateController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $rev = Revision::current();

        if ($request->filled('rev') && (int) $request->query('rev') === $rev) {
            return response()->json(['rev' => $rev, 'changed' => false]);
        }

        $vouchers = Voucher::query()
            ->withSum('pemakaian as terpakai', 'jumlah')
            ->withCount('pemakaian as jumlah_log') // jangan pakai alias "catatan": bentrok dengan kolom catatan
            ->orderByDesc('id')
            ->get()
            ->map(fn (Voucher $v) => [
                'id' => $v->id,
                'klien' => $v->klien,
                'kode' => $v->kode,
                'telepon' => $v->telepon,
                'kuota' => $v->kuota,
                'mulai' => $v->mulai->toDateString(),
                'bulan' => $v->berlaku_bulan,
                'berakhir' => $v->berakhir->toDateString(),
                'catatan' => $v->catatan,
                'terpakai' => (int) $v->terpakai,
                'jumlah_catatan' => (int) $v->jumlah_log,
            ]);

        $terbaru = Pemakaian::query()
            ->with(['user:id,name'])
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (Pemakaian $p) => [
                'id' => $p->id,
                'voucher_id' => $p->voucher_id,
                'jenis' => $p->jenis,
                'tanggal' => $p->tanggal->toDateString(),
                'jumlah' => $p->jumlah,
                'keterangan' => $p->keterangan,
                'oleh' => $p->user?->name,
                'dibuat' => $p->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'rev' => $rev,
            'changed' => true,
            'vouchers' => $vouchers,
            'terbaru' => $terbaru,
        ]);
    }
}
