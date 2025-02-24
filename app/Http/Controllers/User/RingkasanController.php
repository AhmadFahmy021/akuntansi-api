<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\Keuangan;
use App\Models\Ringkasan;
use Illuminate\Http\Request;

class RingkasanController extends Controller
{
    public function ringkasan(Request $request)  {
        $dataJurnal = Jurnal::with(['akun'])->where('perusahaan_id', $request->perusahaan_id)->get()->groupBy('akun.nama');
        // $data = [];
        $selisih = [];
        foreach ($dataJurnal as $key => $value) {
            // $data[$key] = $value[0];
            $data[$key] = Akun::where('nama', $key)->first();
            $selisih123 = Jurnal::where('akun_id', $data[$key]->id)->sum('debit') - Jurnal::where('akun_id', $data[$key]->id)->sum('kredit');
            $selisih[$key] = [
                'akun_id' => $data[$key]->id,
                'saldo_normal' => $data[$key]->saldo_normal,
                // "debit" => Jurnal::where('akun_id', $data[$key]->id)->sum('debit'),
                // "kredit" => Jurnal::where('akun_id', $data[$key]->id)->sum('kredit'),
                'debit' => $data[$key]->saldo_normal === 'debit' ? $selisih123 : 0,
                'kredit' => $data[$key]->saldo_normal === 'kredit' ? $selisih123 : 0,
                "selisih" => Jurnal::where('akun_id', $data[$key]->id)->sum('debit') - Jurnal::where('akun_id', $data[$key]->id)->sum('kredit')
            ];
            // $data = Ringkasan::create($selisih[$key]);
        }
        return response()->json(['data' => $data, 'dataJurnal' => $selisih], 200);
    }
}
