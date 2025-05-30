<?php

namespace App\Http\Controllers\User;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\SubAkun;
use App\Models\Keuangan;
use App\Models\Perusahaan;
use App\Models\NeracaLajur;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Krs;
use Illuminate\Support\Facades\Auth;

class NeracaLajurController extends Controller
{
    public function sebelumPenyesuaian() {

        $krs = Krs::where('user_id', Auth::user()->id)->get()->pluck('id');
        $perusahaan = Perusahaan::whereIn('krs_id', $krs)->where('status', 'online')->first();
        $dataJurnal = Jurnal::with(['akun', 'subAkun', 'perusahaan'])->where('perusahaan_id', $perusahaan->id)->whereRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(bukti, '/', 2), '/', -1) != 'BM'")->get()->sortBy('akun.kode', SORT_NATURAL)->groupBy('akun.nama');

        $dataAkun = [];
        $data = [];
        foreach ($dataJurnal as $key => $value) {
            $dataAkun[$key] = Akun::where('nama', $key)->first();
            $dataSubAkun[$key] = SubAkun::where('akun_id', $dataAkun[$key]->id)->first();
            $debit = $value->sum('debit');
            $kredit = $value->sum('kredit');
            $data[$key] = [
                'akun' => $dataAkun[$key],
                'sub_akun' => $dataSubAkun[$key],
                "debit" => (($debit-$kredit) > 0) ? $debit-$kredit : 0,
                "kredit" => (($debit-$kredit) < 0) ? abs($debit-$kredit): 0,
            ];
        }
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function setelahPenyesuaian() {
        $krs = Krs::where('user_id', Auth::user()->id)->get()->pluck('id');
        $perusahaan = Perusahaan::where('status', 'online')->whereIn('krs_id', $krs)->first();
        // $perusahaan = Perusahaan::where('status', 'online')->first();
        $dataJurnal = Jurnal::with(['akun', 'subAkun', 'perusahaan'])
            ->where('perusahaan_id', $perusahaan->id)
            ->get()
            ->groupBy('akun.nama');

        $dataAkun = [];
        $data = [];

        $akunList = Akun::all();

        foreach ($akunList as $akun) {
            $dataJurnalAkun = isset($dataJurnal[$akun->nama]) ? $dataJurnal[$akun->nama] : collect([]);

            $dataSubAkun = SubAkun::where('akun_id', $akun->id)->first();

            $saldoAwalDebit = 0;
            $saldoAwalKredit = 0;
            $keuangan = Keuangan::where('perusahaan_id', $perusahaan->id)
                                ->where('akun_id', $akun->id)
                                ->first();
            if ($keuangan) {
                $saldoAwalDebit = $keuangan->debit ?? 0;
                $saldoAwalKredit = $keuangan->kredit ?? 0;
            }

            // Hitung total debit dan kredit dari jurnal untuk akun ini
            $totalDebitJurnal = $dataJurnalAkun->sum(function ($jurnal) {
                return $jurnal->debit ?? 0; // Jika debit null, dianggap 0
            });
            $totalKreditJurnal = $dataJurnalAkun->sum(function ($jurnal) {
                return $jurnal->kredit ?? 0;
            });

            // Tambahkan saldo awal ke dalam total debit dan kredit
            $totalDebit = $saldoAwalDebit + $totalDebitJurnal;
            $totalKredit = $saldoAwalKredit + $totalKreditJurnal;

            if ($akun->saldo_normal === 'debit') {

                $saldoAkhir = $totalDebit - $totalKredit;

                $data[$akun->nama] = [
                    'akun' => $akun,
                    'sub_akun' => $dataSubAkun,
                    'debit' => $saldoAkhir > 0 ? $saldoAkhir : 0  ,
                    'kredit' => $saldoAkhir < 0 ? abs($saldoAkhir) : 0,
                ];
            } elseif ($akun->saldo_normal === 'kredit') {
                $saldoAkhir = $totalKredit - $totalDebit;
                $data[$akun->nama] = [
                    'akun' => $akun,
                    'sub_akun' => $dataSubAkun,
                    'debit' => $saldoAkhir < 0 ? abs($saldoAkhir) : 0,
                    'kredit' => $saldoAkhir > 0 ? $saldoAkhir : 0  ,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
