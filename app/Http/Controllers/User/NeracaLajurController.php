<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\NeracaLajur;
use App\Models\Perusahaan;
use App\Models\SubAkun;
use Illuminate\Http\Request;

class NeracaLajurController extends Controller
{
    public function sebelumPenyesuaian() {
        $perusahaan = Perusahaan::where('status', 'online')->first();
        $dataJurnal = Jurnal::with(['akun', 'subAkun', 'perusahaan'])->where('bukti', '!=', 'JP')->where('perusahaan_id', $perusahaan->id)->get()->sortBy('akun.kode', SORT_NATURAL)->groupBy('akun.nama');

        $dataAkun = [];
        $data = [];
        foreach ($dataJurnal as $key => $value) {
            $dataAkun[$key] = Akun::where('nama', $key)->first();
            $dataSubAkun[$key] = SubAkun::where('akun_id', $dataAkun[$key]->id)->first();
            $data[$key] = [
                'akun' => $dataAkun[$key],
                'sub_akun' => $dataSubAkun[$key],

                "debit" =>
                ((
                    Jurnal::where('akun_id', $dataAkun[$key]->id)->where('bukti', '!=', 'JP')->sum('debit')
                    -
                    Jurnal::where('akun_id', $dataAkun[$key]->id)->where('bukti', '!=', 'JP')->sum('kredit')

                ) > 0) ?

                Jurnal::where('akun_id', $dataAkun[$key]->id)->where('bukti', '!=', 'JP')->sum('debit')
                -
                Jurnal::where('akun_id', $dataAkun[$key]->id)->where('bukti', '!=', 'JP')->sum('kredit') : 0 ,

                "kredit" =>
                ((
                Jurnal::where('akun_id', $dataAkun[$key]->id)->where('bukti', '!=', 'JP')->sum('debit')
                -
                Jurnal::where('akun_id', $dataAkun[$key]->id)->where('bukti', '!=', 'JP')->sum('kredit')
                ) < 0) ?

                (Jurnal::where('akun_id', $dataAkun[$key]->id)->where('bukti', '!=', 'JP')->sum('debit')
                -
                Jurnal::where('akun_id', $dataAkun[$key]->id)->where('bukti', '!=', 'JP')->sum('kredit')) * -1  : 0,
            ];

            // if ($dataAkun[$key]->saldo_normal == 'debit') {
            //     # code...
            // }
        }
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
    public function setelahPenyesuaian() {
        $perusahaan = Perusahaan::where('status', 'online')->first();
        $dataJurnal = Jurnal::with(['akun', 'subAkun', 'perusahaan'])->where('perusahaan_id', $perusahaan->id)->get()->groupBy('akun.nama');
        $dataAkun = [];
        $data = [];
        foreach ($dataJurnal as $key => $value) {
            $dataAkun[$key] = Akun::where('nama', $key)->first();
            $dataSubAkun[$key] = SubAkun::where('akun_id', $dataAkun[$key]->id)->first();
            $data[$key] = [
                'akun' => $dataAkun[$key],
                'sub_akun' => $dataSubAkun[$key],
                "debit" =>
                (Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('debit') - Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('kredit') > 0) ?
                Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('debit') - Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('kredit') : 0,

                "kredit" =>
                (Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('debit') - Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('kredit') < 0) ?
                (Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('debit') - Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('kredit')) * -1 : 0,
            ];
        }
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
