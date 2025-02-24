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
    // public function keuangan()  {
    //     $krs = Krs::where('user_id', Auth::user()->id)->get()->pluck('id');
    //     $perusahaan = Perusahaan::whereIn('krs_id', $krs)->where('status', 'online')->first();

    //     $dataJurnal = Jurnal::with(['akun', 'subAkun', 'perusahaan'])->where('perusahaan_id', $perusahaan->id)->get()->groupBy('akun.nama');
    //     $dataAkun = [];
    //     $data = [];
    //     $key2 = 0;
    //     foreach ($dataJurnal as $key => $value) {
    //         $dataAkun[$key] = Akun::where('nama', $key)->first();
    //         $dataSubAkun[$key] = SubAkun::where('akun_id', $dataAkun[$key]->id)->first();
    //         $data[$key2++] = [
    //             'akun' => $dataAkun[$key],
    //             "debit" =>
    //             (Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('debit') - Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('kredit') > 0) ?
    //             Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('debit') - Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('kredit') : 0,

    //             "kredit" =>
    //             (Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('debit') - Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('kredit') < 0) ?
    //             (Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('debit') - Jurnal::where('akun_id', $dataAkun[$key]->id)->sum('kredit')) * -1 : 0,
    //         ];
    //     }
    //     return response()->json([
    //         'success' => true,
    //         'perusahan' => $perusahaan,
    //         'data' => $data,
    //     ]);
    // }
    public function keuangan() {
        $krs = Krs::where('user_id', Auth::user()->id)->pluck('id');
        $perusahaan = Perusahaan::whereIn('krs_id', $krs)->where('status', 'online')->first();
    
        if (!$perusahaan) {
            return response()->json([
                'success' => false,
                'message' => 'Perusahaan tidak ditemukan atau belum online.'
            ], 404);
        }
    
        $dataJurnal = Jurnal::with(['akun', 'subAkun', 'perusahaan'])
            ->where('perusahaan_id', $perusahaan->id)
            ->get()
            ->groupBy('akun.nama');
    
        $data = [];
        $totalKeseluruhan = 0; // Variabel untuk menampung total keseluruhan
    
        foreach ($dataJurnal as $key => $value) {
            $akun = Akun::where('nama', $key)->first();
            if (!$akun) continue;
    
            $totalDebit = Jurnal::where('akun_id', $akun->id)->sum('debit');
            $totalKredit = Jurnal::where('akun_id', $akun->id)->sum('kredit');
            
            // Tentukan total berdasarkan saldo normal
            $total = ($akun->saldo_normal == 'debit') ? ($totalDebit - $totalKredit) : ($totalKredit - $totalDebit);
            $total = abs($total); // Pastikan tidak negatif
    
            $data[] = [
                'akun' => $akun,  // Menampilkan semua field dari akun
                'total' => $total
            ];
    
            // Tambahkan ke total keseluruhan
            $totalKeseluruhan += $total;
        }
    
        return response()->json([
            'success' => true,
            'perusahaan' => $perusahaan, // Menampilkan semua field dari perusahaan
            'data' => $data,
            'total_keseluruhan' => $totalKeseluruhan // Tambahan total keseluruhan dari semua akun
        ]);
    }
    
    public function labarugi() {
        // Mendapatkan perusahaan yang online
        $krs = Krs::where('user_id', Auth::user()->id)->pluck('id');
        $perusahaan = Perusahaan::whereIn('krs_id', $krs)->where('status', 'online')->first();
    
        if (!$perusahaan) {
            return response()->json([
                'success' => false,
                'message' => 'Perusahaan tidak ditemukan atau belum online.'
            ], 404);
        }
    
        // Ambil data akun berdasarkan kategori yang relevan
        $dataAkun = Akun::whereIn('kode', [4100, 4200, 4300, 4400, 4500])->get();
    
        $data = [];
    
        // Mengambil nilai debit dan kredit untuk masing-masing akun
        foreach ($dataAkun as $akun) {
            $totalDebit = Jurnal::where('akun_id', $akun->id)->sum('debit');
            $totalKredit = Jurnal::where('akun_id', $akun->id)->sum('kredit');
    
            // Tentukan nilai yang akan ditampilkan berdasarkan saldo normal
            $nilai = ($akun->saldo_normal == 'debit') ? ($totalDebit - $totalKredit) : ($totalKredit - $totalDebit);
            $nilai = abs($nilai); // Pastikan tidak negatif
    
            // Menyusun data hasil laporan
            $data[$akun->nama] = [
                'akun' => $akun,
                'nilai' => $nilai,
                'debit' => $totalDebit,
                'kredit' => $totalKredit
            ];
        }
    
        // Menghitung Hasil Retur Penjualan & Potongan Penjualan
        $hasilReturDanPotongan = ($data['Retur Penjualan']['nilai'] + $data['Potongan Penjualan']['nilai']);
    
        // Menghitung Penjualan Bersih
        $penjualanBersih = $data['Penjualan']['nilai'] - $hasilReturDanPotongan;
    
        // Menghitung Hasil Pendapatan Jasa Servis Kendaraan & Laba atas Transaksi Tukar Tambah
        $hasilPendapatanJasaDanLaba = ($data['Pendapatan Jasa Servis Kendaraan']['nilai'] + $data['Laba atas Transaksi Tukar Tambah']['nilai']);
    
        // Menghitung Total Penghasilan
        $totalPenghasilan = $penjualanBersih + $hasilPendapatanJasaDanLaba;
    
        // Menyusun hasil laporan
        $result = [
            'Penjualan' => $data['Penjualan']['nilai'],
            'Potongan Penjualan' => $data['Potongan Penjualan']['nilai'],
            'Retur Penjualan' => $data['Retur Penjualan']['nilai'],
            'Hasil Retur Penjualan & Potongan Penjualan' => $hasilReturDanPotongan,
            'Penjualan Bersih' => $penjualanBersih,
            'Pendapatan Jasa Servis Kendaraan' => $data['Pendapatan Jasa Servis Kendaraan']['nilai'],
            'Laba atas Transaksi Tukar Tambah' => $data['Laba atas Transaksi Tukar Tambah']['nilai'],
            'Hasil Pendapatan Jasa Servis Kendaraan & Laba atas Transaksi Tukar Tambah' => $hasilPendapatanJasaDanLaba,
            'Total Penghasilan' => $totalPenghasilan
        ];
    
        // Return response JSON
        return response()->json([
            'success' => true,
            'perusahaan' => $perusahaan,
            'data' => $result
        ]);
    }
    

    
}
