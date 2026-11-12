<?php

namespace App\Http\Controllers;

use App\Models\Pemakaian;
use App\Support\Revision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemakaianController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'voucher_id' => ['required', 'integer', 'exists:vouchers,id'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:100000'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);
        $data['user_id'] = $request->user()->id;

        DB::transaction(function () use ($data) {
            Pemakaian::create($data);
            Revision::bump();
        });

        return response()->json(['ok' => true, 'message' => 'Pemakaian dicatat.']);
    }

    public function destroy(Pemakaian $pemakaian): JsonResponse
    {
        DB::transaction(function () use ($pemakaian) {
            $pemakaian->delete();
            Revision::bump();
        });

        return response()->json(['ok' => true, 'message' => 'Catatan pemakaian dihapus.']);
    }
}
