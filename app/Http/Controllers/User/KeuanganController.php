<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Jurnal;
use App\Models\Keuangan;
use App\Models\Perusahaan;
use App\Models\Krs;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Auth;

class KeuanganController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $krs = Krs::where('user_id', Auth::user()->id)->get()->pluck('id');
        $perusahaan = Perusahaan::whereIn('krs_id', $krs)->where('status', 'online')->first();
        $data = Keuangan::with(['akun','perusahaan', 'sub_akun'])->where('perusahaan_id', $perusahaan->id)->get();
        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request['sub_akun_id'] != null) {
            $request = $request->validate([
                'akun_id' => 'nullable|exists:akun,id',
                'perusahaan_id' => 'required|exists:perusahaan,id',
                'debit' => 'sometimes|nullable',
                'kredit' => 'sometimes|nullable',
                'sub_akun_id' => 'sometimes|nullable',
            ]);
            $exists = Keuangan::where('perusahaan_id', $request['perusahaan_id'])
                                ->where('sub_akun_id', $request['sub_akun_id'])
                                ->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'main_message' => 'Data terdeteksi sama, silahkan isi kan sub akun dan perusahaan yang berbeda',
                    'conten_validation' => [
                        'perusahaan_id' => 'Data perusahaan sudah terpakai.',
                        'sub_akun_id' => 'Data sub akun sudah di pakai',
                    ],
                ]);
            }
        } else {
            $request = $request->validate([
                'akun_id' => 'required|exists:akun,id',
                'perusahaan_id' => 'required|exists:perusahaan,id',
                'debit' => 'sometimes|nullable',
                'kredit' => 'sometimes|nullable',
                'sub_akun_id' => 'sometimes|nullable',
            ]);
            $exists = Keuangan::where('perusahaan_id', $request['perusahaan_id'])
                                ->where('akun_id', $request['akun_id'])
                                ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'main_message' => 'Data terdeteksi sama, silahkan isi kan akun atau perusahaan yang berbeda',
                'perusahaan_id' => 'Data perusahaan sudah terpakai.',
                'akun_id' => 'Data akun sudah di pakai',
            ]);
        }
        }

        Keuangan::create($request);
        return response()->json([
            'success' => true,
            'message' => 'Data Successfully Saved'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $data = Keuangan::with(['akun','perusahaan', 'sub_akun'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => true,
                'message' => 'Data Not Found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $keuangan = Keuangan::with(['akun','perusahaan', 'sub_akun'])->findOrFail($id);
            $request = $request->validate([
                'kredit' => 'sometimes|nullable',
                'debit' => 'sometimes|nullable',
            ]);
            $keuangan->update($request);
            return response()->json([
                'success' => true,
                'message' => 'Data Successfully Changed',
                'data' => $keuangan,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Data Failed Changed',
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            Keuangan::findOrFail($id)->delete();
            return response()->json([
                'success' => true,
                'message' => 'Data Successfully Delete',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Data Failed Changed',
            ], 404);
        }
    }
}
