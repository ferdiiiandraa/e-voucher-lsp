<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Support\Revision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        $voucher = DB::transaction(function () use ($data) {
            $v = Voucher::create($data);
            Revision::bump();

            return $v;
        });

        return response()->json(['ok' => true, 'id' => $voucher->id, 'message' => 'Voucher ditambahkan.']);
    }

    public function update(Request $request, Voucher $voucher): JsonResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($voucher, $data) {
            $voucher->update($data);
            Revision::bump();
        });

        return response()->json(['ok' => true, 'id' => $voucher->id, 'message' => 'Voucher diperbarui.']);
    }

    /**
     * Update sisa secara manual, dua kategori:
     *  - kuota: isi sisa asesi yang benar → dicatat sebagai "koreksi" di riwayat
     *  - masa berlaku: ubah tanggal berakhir
     */
    public function updateSisa(Request $request, Voucher $voucher): JsonResponse
    {
        $terpakai = (int) $voucher->pemakaian()->sum('jumlah');
        $sisaSekarang = $voucher->kuota - $terpakai;

        $data = $request->validate([
            'kategori' => ['required', 'in:kuota,masa'],
            'sisa' => ['required_if:kategori,kuota', 'nullable', 'integer', 'min:0', 'max:'.$voucher->kuota],
            'berakhir' => ['required_if:kategori,masa', 'nullable', 'date_format:Y-m-d', 'after_or_equal:'.$voucher->mulai->toDateString()],
            'keterangan' => ['nullable', 'string', 'max:200'],
        ], [
            'sisa.max' => 'Sisa tidak boleh melebihi kuota ('.$voucher->kuota.'). Untuk menambah kuota, gunakan Edit.',
            'berakhir.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
        ]);

        if ($data['kategori'] === 'kuota') {
            $sisaBaru = (int) $data['sisa'];
            $selisih = $sisaSekarang - $sisaBaru; // positif = sisa berkurang

            if ($selisih === 0) {
                return response()->json(['ok' => true, 'message' => 'Sisa kuota tidak berubah.']);
            }

            $ket = "Penyesuaian sisa {$sisaSekarang} → {$sisaBaru}";
            if (! empty($data['keterangan'])) {
                $ket .= ' · '.$data['keterangan'];
            }

            DB::transaction(function () use ($voucher, $selisih, $ket, $request) {
                $voucher->pemakaian()->create([
                    'jenis' => 'koreksi',
                    'tanggal' => now()->toDateString(),
                    'jumlah' => $selisih,
                    'keterangan' => mb_substr($ket, 0, 255),
                    'user_id' => $request->user()->id,
                ]);
                Revision::bump();
            });

            return response()->json(['ok' => true, 'message' => "Sisa kuota diperbarui menjadi {$sisaBaru} asesi."]);
        }

        if ($data['berakhir'] === $voucher->berakhir->toDateString()) {
            return response()->json(['ok' => true, 'message' => 'Masa berlaku tidak berubah.']);
        }

        DB::transaction(function () use ($voucher, $data) {
            $voucher->update(['berakhir' => $data['berakhir']]);
            Revision::bump();
        });

        return response()->json(['ok' => true, 'message' => 'Masa berlaku diperbarui.']);
    }

    public function destroy(Voucher $voucher): JsonResponse
    {
        DB::transaction(function () use ($voucher) {
            $voucher->delete();
            Revision::bump();
        });

        return response()->json(['ok' => true, 'message' => 'Voucher dihapus.']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'klien' => ['required', 'string', 'max:150'],
            'kode' => ['nullable', 'string', 'max:80'],
            'telepon' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{8,20}$/'],
            'kuota' => ['required', 'integer', 'min:1', 'max:1000000'],
            'mulai' => ['required', 'date_format:Y-m-d'],
            'bulan' => ['required', 'integer', 'min:1', 'max:120'],
            'berakhir' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:mulai'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'telepon.regex' => 'Nomor telepon tidak valid. Contoh: 0812-3456-7890.',
            'berakhir.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
        ]);

        return [
            'klien' => trim($data['klien']),
            'kode' => isset($data['kode']) ? trim($data['kode']) : null,
            'telepon' => Voucher::normalisasiTelepon($data['telepon'] ?? null),
            'kuota' => $data['kuota'],
            'mulai' => $data['mulai'],
            'berlaku_bulan' => $data['bulan'],
            // Bisa diisi manual; bila kosong dihitung dari mulai + masa berlaku
            'berakhir' => $data['berakhir'] ?? Voucher::hitungBerakhir($data['mulai'], (int) $data['bulan']),
            'catatan' => $data['catatan'] ?? null,
        ];
    }
}
