<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Akun;
use App\Models\Krs;
use App\Models\Perusahaan;
use App\Models\SubAkun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PerusahaanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $krs = Krs::with(['mahasiswa', 'kelas'])->where('user_id', $user->id)->get()->pluck('id');
        $data = Perusahaan::with(['kategori', 'krs.mahasiswa', 'krs.kelas'])->whereIn('krs_id', $krs)->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ],200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request = $request->validate([
            'nama' => 'required|string',
            'alamat' => 'required|string',
            'tahun_berdiri' => 'required|integer',
            'status' => 'nullable|string|in:offline,online',
            'kategori_id' => 'required',
            'start_priode' => 'required|date',
            'end_priode' => 'required|date',
            'krs_id' => 'required',
        ]);
        Perusahaan::create($request);
        return response()->json([
            'success' => true,
            'message' => "Data successfully saved",
        ], 201);


    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $data = Perusahaan::with(['kategori', 'krs.mahasiswa', 'krs.kelas'])->findOrFail($id);
            $akun = Akun::where('kategori_id', $data->kategori_id)->get();
            $subAkun = SubAkun::whereIn('akun_id', $akun->pluck('id'))->get();

            return response()->json([
                'success' => true,
                'data' => $data,
                'akun' => $akun,
                'sub_akun' => $subAkun,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => true,
                'message' => "Data Not Found"
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $perusahaan = Perusahaan::with(['kategori', 'krs.mahasiswa', 'krs.kelas'])->findOrFail($id);
            $request = $request->validate([
                'nama' => 'sometimes|string',
                'alamat' => 'sometimes|string',
                'tahun_berdiri' => 'sometimes|integer',
                'status' => 'sometimes|string|in:active,inactive',
                'kategori_id' => 'sometimes',
                'krs_id' => 'sometimes',
            ]);
            $perusahaan->update($request);
            return response()->json([
                'success' => true,
                'message' => 'Data Successfully Changed',
                'data' => $perusahaan,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => true,
                'message' => "Data Failed Changed",
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $perusahaan = Perusahaan::findOrFail($id)->delete();
            return response()->json([
                'success' => true,
                'message' => "Data Successfully Deleted"
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => true,
                'message' => "Data Failed Deleted"
            ], 404);
        }
    }

    public function status(Request $request) {
        try {
            $data = Perusahaan::where('krs_id', $request->krs_id)->where('id', $request->perusahaan_id)->first();
            Perusahaan::where('krs_id', $request->krs_id)->where('id','!=', $request->perusahaan_id)->update(['status'=>'offline']);
            $data->update(['status' => 'online']);
            return response()->json(
                [
                    'success' => true,
                    'message' => "Perusahaan ". $data->nama ." status ".$data->status,
                    'data' => $data,
                ], 200
            );
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'success' => false,
                    'message' => "Failed Change Status",

                ], 404
            );
        }
    }
}
