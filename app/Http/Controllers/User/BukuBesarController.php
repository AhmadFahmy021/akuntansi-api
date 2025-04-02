<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Jurnal;
use App\Models\Krs;
use App\Models\Perusahaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BukuBesarController extends Controller
{
    public function sortData(Request $request) {
        $krs = Krs::where('user_id', Auth::user()->id)->get()->pluck('id');
        $perusahaan = Perusahaan::whereIn('krs_id', $krs)->where('status', 'online')->first();
        $data = Jurnal::where("akun_id", $request->akun_id)->where('perusahaan_id', $perusahaan->id)->orderBy('tanggal', 'asc')->get();
        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }
}
