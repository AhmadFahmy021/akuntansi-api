<?php

namespace App\Http\Controllers\User;

use App\Models\Krs;
use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\SubAkun;
use App\Models\Keuangan;
use App\Models\Perusahaan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\LabaBersihSetelahPajak;

class LaporanController extends Controller
{
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
        $krs = Krs::where('user_id', Auth::user()->id)->pluck('id');
        $perusahaan = Perusahaan::whereIn('krs_id', $krs)->where('status', 'online')->first();
        if (!$perusahaan) {
            return response()->json([
                'success' => false,
                'message' => 'Perusahaan tidak ditemukan atau belum online.'
            ], 404);
        }
    
        // Get all relevant accounts and their codes
        $dataAkun = Akun::whereIn('kode', [4100, 4200, 4300, 4400, 4500, 4600, 4700, 4800, 4900])->get();
        $data = [];
        foreach ($dataAkun as $akun) {
            $totalDebit = Jurnal::where('perusahaan_id', $perusahaan->id)->where('akun_id', $akun->id)->sum('debit');
            $totalKredit = Jurnal::where('perusahaan_id', $perusahaan->id)->where('akun_id', $akun->id)->sum('kredit');
            $nilai = ($akun->saldo_normal == 'debit') ? ($totalDebit - $totalKredit) : ($totalKredit - $totalDebit);
            $nilai = abs($nilai);
            $data[$akun->nama] = [
                'nilai' => $nilai,
                'kode' => $akun->kode,
                'debit' => $totalDebit,
                'kredit' => $totalKredit
            ];
        }
    
        $hasilReturDanPotongan = 0;
        if (isset($data['retur penjualan']) && isset($data['potongan penjualan'])) {
            $hasilReturDanPotongan = $data['retur penjualan']['nilai'] + $data['potongan penjualan']['nilai'];
        }
    
        $penjualanBersih = isset($data['Penjualan']) ? $data['Penjualan']['nilai'] - $hasilReturDanPotongan : 0;
    
        $akunPenghasilan = Akun::where('kode', 'like', '44%')->get();
        $akunPenghasilanData = [];
        $totalPenghasilan = 0;
        foreach ($akunPenghasilan as $akun) {
            $totalDebit = Jurnal::where('perusahaan_id', $perusahaan->id)->where('akun_id', $akun->id)->sum('debit');
            $totalKredit = Jurnal::where('perusahaan_id', $perusahaan->id)->where('akun_id', $akun->id)->sum('kredit');
            $nilai = ($akun->saldo_normal == 'debit') ? ($totalDebit - $totalKredit) : ($totalKredit - $totalDebit);
            $nilai = abs($nilai);
            $akunPenghasilanData[$akun->nama] = [
                'nilai' => $nilai,
                'kode' => $akun->kode
            ];
            $totalPenghasilan += $nilai;
        }
    
        // Prepare result structure with dynamic account codes
        $result['Penghasilan'] = [
            'Penjualan' => [
                'nilai' => $data['Penjualan']['nilai'] ?? 0,
                'kode' => $data['Penjualan']['kode'] ?? null
            ],
            'Potongan Penjualan' => [
                'nilai' => $data['potongan penjualan']['nilai'] ?? 0,
                'kode' => $data['potongan penjualan']['kode'] ?? null
            ],
            'Retur Penjualan' => [
                'nilai' => $data['retur penjualan']['nilai'] ?? 0,
                'kode' => $data['retur penjualan']['kode'] ?? null
            ],
            'Hasil Retur Penjualan & Potongan Penjualan' => [
                'nilai' => $hasilReturDanPotongan,
                'kode' => ($data['retur penjualan']['kode'] ?? null) . '+' . ($data['potongan penjualan']['kode'] ?? null)
            ],
            'Penjualan Bersih' => [
                'nilai' => $penjualanBersih
            ]
        ];
    
        foreach ($akunPenghasilanData as $namaAkun => $detail) {
            $result['Penghasilan'][$namaAkun] = $detail;
        }
    
        $result['Penghasilan']['Total Penghasilan'] = [
            'nilai' => $totalPenghasilan,
        ];
    
        $result['Penghasilan']['Total Penghasilan Akhir'] = [
            'nilai' => $penjualanBersih + $totalPenghasilan
        ];
    
        // Get inventory and purchase related accounts dynamically
        $akunSediaan = Akun::where('kode', 1161)->first();
        $akunBiayaAngkut = Akun::where('kode', 5113)->first();
        $akunPotonganPembelian = Akun::where('kode', 5115)->first();
        $akunReturPembelian = Akun::where('kode', 5511)->first();
        $akunBiayaAdmin = Akun::where('kode', 5211)->first();
        $akunBiayaPemasaran = Akun::where('kode', 5221)->first();
    
        // Calculate inventory values
        $sediaanAwalDebit = 0;
        $sediaanAwalKredit = 0;
        if ($akunSediaan) {
            $keuanganSediaan = Keuangan::where('perusahaan_id', $perusahaan->id)
                                    ->where('akun_id', $akunSediaan->id)
                                    ->first();
            if ($keuanganSediaan) {
                $sediaanAwalDebit = ($akunSediaan->saldo_normal == 'debit') 
                                    ? ($keuanganSediaan->debit - $keuanganSediaan->kredit) 
                                    : 0;
                $sediaanAwalKredit = ($akunSediaan->saldo_normal == 'kredit') 
                                        ? ($keuanganSediaan->kredit - $keuanganSediaan->debit) 
                                        : 0;
            }
        }
    
        $totalDebitSediaan = Jurnal::where('perusahaan_id', $perusahaan->id)
            ->whereHas('akun', function ($query) use ($akunSediaan) {
                $query->where('kode', $akunSediaan->kode ?? null);
            })
            ->sum('debit');
    
        $totalKreditSediaan = Jurnal::where('perusahaan_id', $perusahaan->id)
            ->whereHas('akun', function ($query) use ($akunSediaan) {
                $query->where('kode', $akunSediaan->kode ?? null);
            })
            ->sum('kredit');
    
        $pembelian = intval(Jurnal::where('perusahaan_id', $perusahaan->id)
            ->whereHas('akun', function ($query) use ($akunSediaan) {
                $query->where('kode', $akunSediaan->kode ?? null);
            })
            ->sum('debit'));
    
        $biayaAngkutPembelian = Jurnal::where('perusahaan_id', $perusahaan->id)
            ->whereHas('akun', function($query) use ($akunBiayaAngkut) {
                $query->where('kode', $akunBiayaAngkut->kode ?? null);
            })
            ->sum('debit') - Jurnal::where('perusahaan_id', $perusahaan->id)
                ->whereHas('akun', function($query) use ($akunBiayaAngkut) {
                    $query->where('kode', $akunBiayaAngkut->kode ?? null);
                })
                ->sum('kredit');
    
        $potonganPembelian = Jurnal::where('perusahaan_id', $perusahaan->id)
            ->whereHas('akun', function($query) use ($akunPotonganPembelian) {
                $query->where('kode', $akunPotonganPembelian->kode ?? null);
            })
            ->sum('debit') - Jurnal::where('perusahaan_id', $perusahaan->id)
                ->whereHas('akun', function($query) use ($akunPotonganPembelian) {
                    $query->where('kode', $akunPotonganPembelian->kode ?? null);
                })
                ->sum('kredit');
    
        $returPembelian = Jurnal::where('perusahaan_id', $perusahaan->id)
            ->whereHas('akun', function($query) use ($akunReturPembelian) {
                $query->where('kode', $akunReturPembelian->kode ?? null);
            })
            ->sum('debit') - Jurnal::where('perusahaan_id', $perusahaan->id)
                ->whereHas('akun', function($query) use ($akunReturPembelian) {
                    $query->where('kode', $akunReturPembelian->kode ?? null);
                })
                ->sum('kredit');
    
        if ($potonganPembelian > 0) {
            $potonganPembelian = -$potonganPembelian;
        }
    
        $totalPembelian = $pembelian + $biayaAngkutPembelian + $potonganPembelian - $returPembelian;
        $barangSiapDijual = $sediaanAwalDebit + $totalPembelian;
        $sediaanAkhir = ($totalDebitSediaan + $sediaanAwalDebit) - ($totalKreditSediaan + $sediaanAwalKredit);
        $kosBarangTerjual = $barangSiapDijual - $sediaanAkhir;
        $labaKotor = $result['Penghasilan']['Total Penghasilan Akhir']['nilai'] - $kosBarangTerjual;
    
        $result['Kos Barang Terjual'] = [
            'Sediaan' => [
                'nilai' => $sediaanAwalDebit,
                'kode' => $akunSediaan->kode ?? null
            ],
            'Pembelian' => [
                'nilai' => $pembelian,
                'kode' => $akunSediaan->kode ?? null
            ],
            'Biaya Angkut Pembelian' => [
                'nilai' => $biayaAngkutPembelian,
                'kode' => $akunBiayaAngkut->kode ?? null
            ],
            'Potongan Pembelian' => [
                'nilai' => $potonganPembelian,
                'kode' => $akunPotonganPembelian->kode ?? null
            ],
            'Retur Pembelian' => [
                'nilai' => $returPembelian,
                'kode' => $akunReturPembelian->kode ?? null
            ],
            'Total' => [
                'nilai' => $totalPembelian,
                'kode' => ($akunSediaan->kode ?? null) . '+' . 
                         ($akunBiayaAngkut->kode ?? null) . '+' . 
                         ($akunPotonganPembelian->kode ?? null) . '-' . 
                         ($akunReturPembelian->kode ?? null)
            ],
            'Barang Siap Dijual' => [
                'nilai' => $barangSiapDijual,
                'kode' => $akunSediaan->kode. ' + Total',
            ],
            'Sediaan Akhir' => [
                'nilai' => $sediaanAkhir,
                'kode' => $akunSediaan->kode ?? null
            ],
            'Kos Barang Terjual' => [
                'nilai' => $kosBarangTerjual,
            ],
            'Laba Kotor' => [
                'nilai' => $labaKotor,
            ]
        ];
    
        // Calculate operational expenses
        $biayaAdministrasiUmum = Jurnal::where('perusahaan_id', $perusahaan->id)
            ->whereHas('akun', function($query) use ($akunBiayaAdmin) {
                $query->where('kode', $akunBiayaAdmin->kode ?? null);
            })
            ->sum('debit') - Jurnal::where('perusahaan_id', $perusahaan->id)
                ->whereHas('akun', function($query) use ($akunBiayaAdmin) {
                    $query->where('kode', $akunBiayaAdmin->kode ?? null);
                })
                ->sum('kredit');
    
        $biayaPemasaran = Jurnal::where('perusahaan_id', $perusahaan->id)
            ->whereHas('akun', function($query) use ($akunBiayaPemasaran) {
                $query->where('kode', $akunBiayaPemasaran->kode ?? null);
            })
            ->sum('debit') - Jurnal::where('perusahaan_id', $perusahaan->id)
                ->whereHas('akun', function($query) use ($akunBiayaPemasaran) {
                    $query->where('kode', $akunBiayaPemasaran->kode ?? null);
                })
                ->sum('kredit');
    
        $totalBiayaUsaha = $biayaAdministrasiUmum + $biayaPemasaran;
        $labaBersihUsaha = $labaKotor - $totalBiayaUsaha;
    
        $result['Biaya Usaha'] = [
            'Biaya Administrasi dan Umum' => [
                'nilai' => $biayaAdministrasiUmum,
                'kode' => $akunBiayaAdmin->kode ?? null
            ],
            'Biaya Pemasaran' => [
                'nilai' => $biayaPemasaran,
                'kode' => $akunBiayaPemasaran->kode ?? null
            ],
            'Total Biaya Usaha' => [
                'nilai' => $totalBiayaUsaha,
                'kode' => ($akunBiayaAdmin->kode ?? null) . '+' . ($akunBiayaPemasaran->kode ?? null)
            ],
            'Laba Bersih Usaha' => [
                'nilai' => $labaBersihUsaha,
            ]
        ];
    
       // Ambil data akun untuk Pendapatan di Luar Usaha (kode like '611%')
$akunPendapatanDiluarUsaha = Akun::where('kode', 'like', '611%')->pluck('kode')->toArray();
$kodePendapatanDiluarUsaha = implode(' + ', $akunPendapatanDiluarUsaha);

// Hitung total nilai Pendapatan di Luar Usaha
$pendapatanDiluarUsaha = Jurnal::where('perusahaan_id', $perusahaan->id)
    ->whereHas('akun', function ($query) {
        $query->where('kode', 'like', '611%');
    })
    ->get()
    ->reduce(function ($carry, $item) {
        $carry += abs($item->debit) - abs($item->kredit);
        return $carry;
    }, 0);

$pendapatanDiluarUsahareal = abs($pendapatanDiluarUsaha);

// Ambil data akun untuk Biaya di Luar Usaha (kode like '62%')
$akunBiayaDiluarUsaha = Akun::where('kode', 'like', '62%')->pluck('kode')->toArray();
$kodeBiayaDiluarUsaha = implode(' + ', $akunBiayaDiluarUsaha);

// Hitung total nilai Biaya di Luar Usaha
$biayaDiluarUsaha = Jurnal::where('perusahaan_id', $perusahaan->id)
    ->whereHas('akun', function ($query) {
        $query->where('kode', 'like', '62%');
    })
    ->get()
    ->reduce(function ($carry, $item) {
        $carry += abs($item->debit) - abs($item->kredit);
        return $carry;
    }, 0);

$biayaDiluarUsahareal = abs($biayaDiluarUsaha);

$totalPendapatanBiayaDiluarUsaha = $pendapatanDiluarUsahareal - $biayaDiluarUsahareal;

$labaBersihSebelumLabaRugiLuarBiasa = $labaBersihUsaha + $totalPendapatanBiayaDiluarUsaha;

$result['Pendapatan dan Biaya Diluar Usaha'] = [
    'Pendapatan diluar Usaha' => [
        'nilai' => $pendapatanDiluarUsahareal,
        'kode' => $kodePendapatanDiluarUsaha 
    ],
    'Biaya Diluar Usaha' => [
        'nilai' => $biayaDiluarUsahareal,
        'kode' => $kodeBiayaDiluarUsaha 
    ],
    'Total Pendapatan dan Biaya Diluar Usaha' => [
        'nilai' => $totalPendapatanBiayaDiluarUsaha,
    ],
    'Laba Bersih Sebelum Laba-Rugi Luar Biasa' => [
        'nilai' => $labaBersihSebelumLabaRugiLuarBiasa,
    ]
];
    
        // Calculate extraordinary items
        $akunLabaLuarBiasa = Akun::where('kode', '6400')->first();
        $akunRugiLuarBiasa = Akun::where('kode', '6300')->first();
    
        $labaLuarBiasa = Jurnal::where('perusahaan_id', $perusahaan->id)
            ->whereHas('akun', function($query) use ($akunLabaLuarBiasa) {
                $query->where('kode', $akunLabaLuarBiasa->kode ?? null);
            })
            ->sum('debit') - Jurnal::where('perusahaan_id', $perusahaan->id)
                ->whereHas('akun', function($query) use ($akunLabaLuarBiasa) {
                    $query->where('kode', $akunLabaLuarBiasa->kode ?? null);
                })
                ->sum('kredit');
    
        $labaLuarBiasareal = abs($labaLuarBiasa);
    
        $rugiLuarBiasa = Jurnal::where('perusahaan_id', $perusahaan->id)
            ->whereHas('akun', function($query) use ($akunRugiLuarBiasa) {
                $query->where('kode', $akunRugiLuarBiasa->kode ?? null);
            })
            ->sum('debit') - Jurnal::where('perusahaan_id', $perusahaan->id)
                ->whereHas('akun', function($query) use ($akunRugiLuarBiasa) {
                    $query->where('kode', $akunRugiLuarBiasa->kode ?? null);
                })
                ->sum('kredit');
    
        $rugiLuarBiasareal = abs($rugiLuarBiasa);
        $totalLabaRugi = $labaLuarBiasareal - $rugiLuarBiasareal;
        $labaBersihSebelumPajak = $labaBersihSebelumLabaRugiLuarBiasa + $totalLabaRugi;
        if ($labaBersihSebelumPajak > 0) {
            $labaBersihSetelahPajak = $labaBersihSebelumPajak * 0.75; 
        } else {
            $labaBersihSetelahPajak = $labaBersihSebelumPajak; 
        }
    
        LabaBersihSetelahPajak::updateOrCreate(
            ['perusahaan_id' => $perusahaan->id],
            ['laba_bersih_setelah_pajak' => $labaBersihSetelahPajak]
        );
    
        $result['Laba/Rugi Luar Biasa'] = [
            'Laba Luar Biasa' => [
                'nilai' => $labaLuarBiasareal,
                'kode' => $akunLabaLuarBiasa->kode ?? null
            ],
            'Rugi Luar Biasa' => [
                'nilai' => $rugiLuarBiasareal,
                'kode' => $akunRugiLuarBiasa->kode ?? null
            ],
            'Total Laba/Rugi' => [
                'nilai' => $totalLabaRugi,
                'kode' => ($akunLabaLuarBiasa->kode ?? null) . '-' . ($akunRugiLuarBiasa->kode ?? null)
            ],
            'Laba Bersih Sebelum Pajak' => [
                'nilai' => $labaBersihSebelumPajak,
            ],
            'Laba Bersih Setelah Pajak' => [
                'nilai' => $labaBersihSetelahPajak,
            ]
        ];
        return response()->json([
            'success' => true,
            'perusahaan' => $perusahaan,
            'data' => $result
        ]);
    }

    public function Ekuitas() {
        // Ambil perusahaan yang sedang aktif (sama seperti di method labarugi)
        $krs = Krs::where('user_id', Auth::user()->id)->pluck('id');
        $perusahaan = Perusahaan::whereIn('krs_id', $krs)->where('status', 'online')->first();
    
        if (!$perusahaan) {
            return response()->json([
                'success' => false,
                'message' => 'Perusahaan tidak ditemukan atau belum online.'
            ], 404);
        }
    
        // Array untuk menyimpan hasil output
        $result = [];
        $dataAkun31 = [];
        $dataAkun32 = [];
    
        // Loop untuk mengambil data akun dengan kode yang dimulai dengan "31"
        $akunQuery = Akun::where('kode', 'like', '31%')->get(); // Mencari semua akun yang kode-nya diawali dengan "31"
    
        // Perulangan untuk setiap akun yang ditemukan
        foreach ($akunQuery as $akun) {
    
            $sediaanAwalDebit = 0;
            $sediaanAwalKredit = 0;
    
            $keuanganSediaan = Keuangan::where('perusahaan_id', $perusahaan->id)
                ->where('akun_id', $akun->id)
                ->first();
    
            if ($keuanganSediaan) {
                $sediaanAwalDebit = ($akun->saldo_normal == 'debit') 
                                    ? ($keuanganSediaan->debit - $keuanganSediaan->kredit) 
                                    : 0;
    
                $sediaanAwalKredit = ($akun->saldo_normal == 'kredit') 
                                    ? ($keuanganSediaan->kredit - $keuanganSediaan->debit) 
                                    : 0;
            }
    
            // Hitung total debit dan kredit dari tabel Jurnal
            $totalDebit = Jurnal::where('perusahaan_id', $perusahaan->id)
                ->where('akun_id', $akun->id)
                ->sum('debit');
    
            $totalKredit = Jurnal::where('perusahaan_id', $perusahaan->id)
                ->where('akun_id', $akun->id)
                ->sum('kredit');
    
            // Hitung saldo akhir (Saldo Awal + SUM Debit) - (Saldo Awal + SUM Kredit)
            $saldoAkhir = ($sediaanAwalDebit + $totalDebit) - ($sediaanAwalKredit + $totalKredit);
            $saldoAkhirreal = abs($saldoAkhir);
    
            $dataAkun31[$akun->kode] = [
                'nama_akun' => $akun->nama,
                'saldo_akhir' => $saldoAkhirreal,
            ];
    
            // Tambahkan ke hasil output
            $result[] = [
                'Nama Akun' => $akun->nama,
                'Kode Akun' => $akun->kode,
                'Saldo Awal per 1 Januari 2025' => $saldoAkhirreal,
            ];
        }
    
        // Ambil laba bersih setelah pajak dari properti yang ada
        $labaBersihSetelahPajak = (int)LabaBersihSetelahPajak::where('perusahaan_id', $perusahaan->id)
        ->value('laba_bersih_setelah_pajak') ?? 0;
        $pembagianLaba = $labaBersihSetelahPajak / 3;
    
        $result[] = [
            'Laba Bersih Setelah Pajak' => $labaBersihSetelahPajak,
        ];
    
        // Loop untuk menambahkan informasi laba bersih dan pembagian laba ke hasil output
        foreach ($akunQuery as $akun) {
            $result[] = [
                'Laba (' . $akun->nama . ')' => $pembagianLaba,
            ];
        }
    
        // Loop untuk mengambil data akun dengan kode yang dimulai dengan "32"
        $akunQuery32 = Akun::where('kode', 'like', '32%')->get(); // Mencari semua akun yang kode-nya diawali dengan "31"
    
        // Perulangan untuk setiap akun yang ditemukan
        foreach ($akunQuery32 as $akun) {
    
            $sediaanAwalDebit32 = 0;
            $sediaanAwalKredit32 = 0;
    
            $keuanganSediaan = Keuangan::where('perusahaan_id', $perusahaan->id)
                ->where('akun_id', $akun->id)
                ->first();
    
            if ($keuanganSediaan) {
                $sediaanAwalDebit32 = ($akun->saldo_normal == 'debit') 
                                    ? ($keuanganSediaan->debit - $keuanganSediaan->kredit) 
                                    : 0;
    
                $sediaanAwalKredit32 = ($akun->saldo_normal == 'kredit') 
                                    ? ($keuanganSediaan->kredit - $keuanganSediaan->debit) 
                                    : 0;
            }
    
            // Hitung total debit dan kredit dari tabel Jurnal
            $totalDebit32 = Jurnal::where('perusahaan_id', $perusahaan->id)
                ->where('akun_id', $akun->id)
                ->sum('debit');
    
            $totalKredit32 = Jurnal::where('perusahaan_id', $perusahaan->id)
                ->where('akun_id', $akun->id)
                ->sum('kredit');
    
            // Hitung saldo akhir (Saldo Awal + SUM Debit) - (Saldo Awal + SUM Kredit)
            $saldoAkhir32 = ($sediaanAwalDebit32 + $totalDebit32) - ($sediaanAwalKredit32 + $totalKredit32);
            $saldoAkhirreal32 = abs($saldoAkhir32);
    
            $dataAkun32[$akun->kode] = [
                'nama_akun' => $akun->nama,
                'saldo_akhir' => $saldoAkhirreal32,
            ];
    
            // Tambahkan ke hasil output
            $result[] = [
                'Nama Akun' => $akun->nama,
                'Kode Akun' => $akun->kode,
                'Saldo Awal per 1 Januari 2025' => $saldoAkhirreal32,
            ];
        }
    
        $dataAkhir = [];
        foreach ($dataAkun31 as $namaAkun => $akun31) {
            // Mengubah kode 31 menjadi 32 (misal: 3111 -> 3211)
            $kode32 = '32' . substr($namaAkun, 2);
    
            // Ambil saldo akhir yang sesuai dengan kode yang sudah diubah
            $saldoAkhirreal32 = $dataAkun32[$kode32]['saldo_akhir'] ?? 0;
    
            // Hitung data akhir dengan menggunakan saldo dari akun 31 dan akun 32
            $dataAkhir[$akun31['nama_akun'] . ' per 31 Januari 2025'] = 
                $akun31['saldo_akhir'] + $pembagianLaba - $saldoAkhirreal32;
        }
    
        // Tambahkan DATA AKHIR ke hasil output
        $result[]['DATA AKHIR'] = $dataAkhir;
    
        // Kembalikan hasil dalam format JSON
        return response()->json([
            'success' => true,
            'perusahaan' => $perusahaan,
            'data' => $result  // "data" now has a list of objects
        ]);
    }
   

    public function posisi_keuangan(){
        $krs = Krs::where('user_id', Auth::user()->id)->pluck('id');
        $perusahaan = Perusahaan::whereIn('krs_id', $krs)->where('status', 'online')->first();
        $akun = Akun::where('kategori_id', $perusahaan->kategori_id)->get();

        $dataAkunAsetLancar=[];
        $dataAkunAsetTetap=[];
        $dataAkunKewajiban=[];
        $dataAkunEkuitas=[];
        $key2 = 0;
        foreach ($akun as $key => $value) {
            if((int)substr(strval($value['kode']), 0, 2) == 11 && $value['kode'] != 1121){
                if ($value['kode'] != 1121) {
                    $totalAllKredit = Jurnal::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('kredit')+Keuangan::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('kredit');
                    $totalAllDebit = Jurnal::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('debit')+Keuangan::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('debit');
                    $dataAkunAsetLancar[$key2++] = [
                        "akun" =>$value,
                        "kode" =>$value['kode'],
                        'total' => abs($totalAllDebit-$totalAllKredit),
                    ];

                }
            } else if((int)substr(strval($value['kode']), 0, 2) == 12 | $value['kode'] == 1121){
                // if ($value['kode'] == 1121) {
                    $totalAllKredit = Jurnal::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('kredit')+Keuangan::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('kredit');
                    $totalAllDebit = Jurnal::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('debit')+Keuangan::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('debit');
                    $dataAkunAsetTetap[$key2++] = [
                        "akun" =>$value,
                        "kode" =>$value['kode'],
                        'total' => abs($totalAllDebit-$totalAllKredit),
                    ];

                // }
            } else if(in_array((int)substr(strval($value['kode']), 0, 2), [21, 22]) ){
                $totalAllKredit = Jurnal::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('kredit')+Keuangan::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('kredit');
                $totalAllDebit = Jurnal::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('debit')+Keuangan::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('debit');
                $dataAkunKewajiban[$key2++] = [
                    "akun" =>$value,
                    "kode" =>$value['kode'],
                    'total' => abs($totalAllDebit-$totalAllKredit),
                ];
            } else if(in_array($value['kode'], [3111, 3112, 3121]) ){
                $totalKredit = Jurnal::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('kredit')+Keuangan::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('kredit');
                $totalDebit = Jurnal::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('debit')+Keuangan::where('akun_id', $value['id'])->where('perusahaan_id', $perusahaan->id)->get()->sum('debit');
                $dataAkunEkuitas[$key2++] = [
                    "akun" =>$value,
                    "kode" =>$value['kode'],
                    'total' => abs($totalDebit-$totalKredit),
                ];
            }
        }


        $dataAsetLancar = [];
        $dataAsetTetap = [];
        $dataKewajiban = [];
        $dataEkuitas = [];

        $totalAsetLancar = 0;
        $totalAsetTetap = 0;
        $totalKewajiban = 0;
        $totalEkuitas = 0;

        foreach ($dataAkunAsetLancar as $key => $value) {
            if ($value['kode'] != 1153) {
                $totalAsetLancar += $value['total'];
            } else {
                $totalAsetLancar -= $value['total'];
            }
        }
        $dataAsetLancar['akun'] = $dataAkunAsetLancar;
        $dataAsetLancar['total_keseluruhan'] = $totalAsetLancar;

        foreach ($dataAkunAsetTetap as $key => $value) {
            if (!in_array($value['kode'], [1225, 1222, 1223, 1224])) {
                $totalAsetTetap += $value['total'];
            } else {
                $totalAsetTetap -= $value['total'];
            }
            // $dataAsetTetap[$key] = $value['total'];
        }
        $dataAsetTetap['akun'] = $dataAkunAsetTetap;
        $dataAsetTetap['total_keseluruhan'] = $totalAsetTetap;

        foreach ($dataAkunKewajiban as $key => $value) {
                $totalKewajiban += $value['total'];
        }
        $dataKewajiban['akun'] = $dataAkunKewajiban;
        $dataKewajiban['total_keseluruhan'] = $totalKewajiban;

        foreach ($dataAkunEkuitas as $key => $value) {
                $totalEkuitas += $value['total'];
        }
        $dataEkuitas['akun'] = $dataAkunEkuitas;
        $dataEkuitas['total_keseluruhan'] = $totalEkuitas;

        $dataTotalAset = $dataAsetTetap['total_keseluruhan']+$dataAsetLancar['total_keseluruhan'];
        $dataTotalKewajibanModal = $dataKewajiban['total_keseluruhan']+$dataEkuitas['total_keseluruhan'];

        $data = [
            "success" => true,
            "perusahaan" => $perusahaan,
            "aset_lancar" => $dataAsetLancar,
            "aset_tetap" => $dataAsetTetap,
            "kewajiban" => $dataKewajiban,
            "ekuitas" => $dataEkuitas,
            "total_aset" => $dataTotalAset,
            "total_kewajiban_ekuitas" => $dataTotalKewajibanModal,
        ];

        return response()->json($data, 200);
    }


}
