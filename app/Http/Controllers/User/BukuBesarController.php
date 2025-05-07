<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\Keuangan;
use App\Models\Krs;
use App\Models\Perusahaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use function PHPUnit\Framework\isNull;

class BukuBesarController extends Controller
{
    public function sortData(Request $request) {
        $krs = Krs::where('user_id', Auth::user()->id)->get()->pluck('id');
        $perusahaan = Perusahaan::whereIn('krs_id', $krs)->where('status', 'online')->first();
        $akun = Akun::where('id', $request->akun_id)->first();

        $data = null;

        $dataTotalDebit = Jurnal::with('akun')->where("akun_id", $request->akun_id)->where('perusahaan_id', $perusahaan->id)->orderBy('tanggal', 'asc')->get()->sum('debit');

        $dataTotalKredit = Jurnal::with('akun')->where("akun_id", $request->akun_id)->where('perusahaan_id', $perusahaan->id)->orderBy('tanggal', 'asc')->get()->sum('kredit');

        $jurnal = Jurnal::with('akun')->where("akun_id", $request->akun_id)->where('perusahaan_id', $perusahaan->id)->orderBy('tanggal', 'asc')->get();

        $keuangan = Keuangan::with('akun')->where('perusahaan_id', $perusahaan->id)->where('akun_id', $request->akun_id)->first();

        if ($akun->saldo_normal == "debit") {

            $total = ($keuangan->debit != null) ? ($dataTotalDebit-$dataTotalKredit) + $keuangan->debit:($dataTotalDebit-$dataTotalKredit) - $keuangan->kredit;

            $data = [
                'keuangan' => $keuangan,
                'jurnal' => $jurnal,
                'total' => $total,
                'totalDebit' => $dataTotalDebit,
                'totalKredit' => $dataTotalKredit,
            ];

        } else if ($akun->saldo_normal == "kredit") {

            $total = ($keuangan->kredit != null) ? ($dataTotalKredit-$dataTotalDebit) + $keuangan->kredit:($dataTotalKredit-$dataTotalDebit) - $keuangan->debit;

            $data = [
                'keuangan' => $keuangan,
                'jurnal' => $jurnal,
                'total' => $total,
                'totalDebit' => $dataTotalDebit,
                'totalKredit' => $dataTotalKredit,
            ];
        }

        return response()->json([
            'success' => true,
            'data'=> $data,
        ], 200);
    }
}
