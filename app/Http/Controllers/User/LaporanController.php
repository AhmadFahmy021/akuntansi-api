<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\Krs;
use App\Models\Perusahaan;
use App\Models\SubAkun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LaporanController extends Controller
{
    public function keuangan()  {
        $krs = Krs::where('user_id', Auth::user()->id)->get()->pluck('id');
        $perusahaan = Perusahaan::whereIn('krs_id', $krs)->where('status', 'online')->first();

        $dataJurnal = Jurnal::with(['akun', 'subAkun', 'perusahaan'])->where('perusahaan_id', $perusahaan->id)->get()->groupBy('akun.nama');
        $dataAkun = [];
        $data = [];
        $key2 = 0;
        foreach ($dataJurnal as $key => $value) {
            $dataAkun[$key] = Akun::where('nama', $key)->first();
            $dataSubAkun[$key] = SubAkun::where('akun_id', $dataAkun[$key]->id)->first();
            $data[$key2++] = [
                'akun' => $dataAkun[$key],
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
            'perusahan' => $perusahaan,
            'data' => $data,
        ]);
    }
}
