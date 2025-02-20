<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Instruktur\AkunController;
use App\Http\Controllers\Instruktur\KelasController;
use App\Http\Controllers\Instruktur\UsersController;
use App\Http\Controllers\Instruktur\KategoriController;
use App\Http\Controllers\Instruktur\KelasInstrukturController;
use App\Http\Controllers\Instruktur\MonitoringController;
use App\Http\Controllers\Instruktur\ProfileInstrukturController;
use App\Http\Controllers\User\BukuBesarController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\JurnalController;
use App\Http\Controllers\User\KeuanganController;
use App\Http\Controllers\User\KRSController;
use App\Http\Controllers\User\LaporanController;
use App\Http\Controllers\User\NeracaLajurController;
use App\Http\Controllers\User\PerusahaanController;
use App\Http\Controllers\User\ProfileMahasiswaController;
use App\Http\Controllers\User\RingkasanController;
use App\Http\Controllers\User\SubAkunController;

Route::prefix('/instruktur')->group(
    function () {
        Route::post('/login', [AuthController::class, 'login_instruktur']);
        Route::post('/register', [AuthController::class, 'register_instruktur']);
        Route::post('/verifikasi', [AuthController::class, 'verifikasi']);
        Route::post('/resendotp', [AuthController::class, 'resendOTP']);

        Route::apiResource('/users', UsersController::class);
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);

            Route::apiResource('/kategori', KategoriController::class);
            Route::apiResource('/akun', AkunController::class);
            Route::apiResource('/kelas', KelasController::class);
            Route::apiResource('/krs', KrsController::class);
            Route::apiResource('/profileinstruktur', ProfileInstrukturController::class);
            Route::apiResource('/kelasinstruktur', KelasInstrukturController::class);
            // Route::get('/all', [AuthController::class, 'getAllData']); // Route untuk semua data
            // Route::get('/{id}', [AuthController::class, 'getDataById']); // Route untuk data by ID
            Route::get('/monitoring', [MonitoringController::class, 'index']);

            // Menampilkan anggota kelas berdasarkan kelas_id
            Route::get('/monitoring/{kelas_id}/anggota', [MonitoringController::class, 'showKelasAnggota']);
            Route::get('/kelas/{profile_instruktur_id}', [MonitoringController::class, 'getKelasByProfileInstruktur']);
            Route::get('/anggota/{profile_instruktur_id}', [MonitoringController::class, 'getAnggotaByProfileInstruktur']);

        });

    }
);

Route::prefix('/mahasiswa')->group(
    function () {
        Route::post('/login', [AuthController::class, 'login_mahasiswa']);
        Route::post('/register', [AuthController::class, 'register_mahasiswa']);
        Route::post('/verifikasi', [AuthController::class, 'verifikasi']);
        Route::post('/resendotp', [AuthController::class, 'resendOTP']);

        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/ringkasan', [RingkasanController::class, 'ringkasan']);
            Route::get('/bukubesar/sort', [BukuBesarController::class, 'sortData']);
            Route::get('/neracalajur/sebelumpenyesuaian', [NeracaLajurController::class, 'sebelumPenyesuaian']);
            Route::get('/neracalajur/setelahpenyesuaian', [NeracaLajurController::class, 'setelahPenyesuaian']);
            Route::post('/perusahaan/status', [PerusahaanController::class, 'status']);
            Route::get('/dashboard', [DashboardController::class, 'dashboard']);
            Route::post('/dashboard/chart', [DashboardController::class, 'chartDashboard']);
            Route::get('/laporan/keuangan', [LaporanController::class, 'keuangan']);
            // Route::post('/dashboard/chart', [DashboardController::class, 'chartDashboard']);

            Route::apiResource('/krs', KRSController::class);
            Route::apiResource('/perusahaan', PerusahaanController::class);
            Route::apiResource('/jurnal', JurnalController::class);
            Route::apiResource('/subakun', SubAkunController::class);
            Route::apiResource('/keuangan', KeuanganController::class);
            Route::apiResource('/profile', ProfileMahasiswaController::class);
        });
    }
);

Route::get('/test', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
