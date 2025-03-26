<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Jurnal;
use Illuminate\Http\Request;

class BukuBesarController extends Controller
{
    public function sortData(Request $request) {
        // $krs =
        $data = Jurnal::where("akun_id", $request->akun_id)->orderBy('tanggal', 'asc')->get();
        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }
}
