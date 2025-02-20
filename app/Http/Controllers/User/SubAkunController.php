<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Akun;
use App\Models\SubAkun;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubAkunController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = SubAkun::with(['akun', 'perusahaan'])->get();
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
        $validated = $request->validate([
            'kode' => 'required|string',
            'nama' => 'required|string',
            'akun_id' => 'required|uuid',
            'perusahaan_id' => 'required|uuid'
        ]);
        $akun = Akun::where('id', $request->akun_id)->first();
        $validated['kode'] = $akun->kode.'.'.$request->kode;
        if ($akun->status == 'open') {
            SubAkun::create($validated);
            return response()->json([
                'success' => true,
                'message' => "Data Successfully Saved",
            ], 200);
        } else {
            throw ValidationException::withMessages([
                "akun_id" => "Data akun berstatus close, tidak bisa digunakan",
            ]);

        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $data = SubAkun::with(['akun', 'perusahaan'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => true,
                'message' => "Data Not Found",
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // try {
            $subAkun = SubAkun::with(['akun', 'perusahaan'])->findOrFail($id);
            $validated = $request->validate([
                'kode' => 'sometimes|string',
                'nama' => 'sometimes|string',
                'akun_id' => 'sometimes|uuid',
                'perusahaan_id' => 'sometimes|uuid'
            ]);

            if (array_key_exists('akun_id', $validated)) {
                $akun = Akun::where('id', $request->akun_id)->first();
                $validated['kode'] = $akun->kode.'.'.$validated['kode'];
            } else if (array_key_exists('kode', $validated)) {
                $kode = explode('.', $subAkun->kode);
                // $kodeSubAkun = end($kode);
                $kodeAkun = $kode[0];
                $validated['kode'] = $kodeAkun.'.'.$validated['kode'];
                // dd($kode);
                // $validated['kode'] = ;
            } 
            // dd(array_key_exists('akun_id', $validated));
            $subAkun->update($validated);
            return response()->json([
                'success' => true,
                'message' => 'Data Successfully Changed',
                'data' => $subAkun
            ],200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => true,
        //         'message' => 'Data Failed Changed',
        //     ],404);
        // }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            SubAkun::findOrFail($id)->delete();
            return response()->json([
                'success' => true,
                'message' => "Data Successfully Deleted"
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => true,
                'message' => "Data Failed Deleted"
            ], 200);
        }
    }
}
