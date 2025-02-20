<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Krs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KRSController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Mengambil semua data KRS dengan relasi kelas dan mahasiswa
        $krs = Krs::with(['kelas', 'mahasiswa'])->get();

        return response()->json([
            'success' => true,
            'data' => $krs
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            // 'user_id' => 'required|exists:users,id',
            'kelas_id' => 'required|exists:kelas,id',
        ]);
        $validated['user_id'] = Auth::user()->id;
        // Membuat data KRS baru
        $krs = Krs::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'KRS berhasil ditambahkan',
            'data' => $krs
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Menampilkan detail data KRS berdasarkan ID
        try {
            $krs = Krs::with(['kelas', 'mahasiswa'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $krs
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'KRS tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'kelas_id' => 'sometimes|exists:kelas,id',
        ]);
        try {
            // Cari data KRS
            $krs = Krs::findOrFail($id);

            // Update data KRS
            $krs->update($request->only('user_id', 'kelas_id'));

            return response()->json([
                'success' => true,
                'message' => 'KRS berhasil diperbarui',
                'data' => $krs
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'KRS tidak ditemukan'
            ], 404);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            // Cari data KRS
            $krs = Krs::find($id);


            // Hapus data KRS
            $krs->delete();

            return response()->json([
                'success' => true,
                'message' => 'KRS berhasil dihapus'
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'KRS tidak ditemukan'
            ], 404);
        }
        if (!$krs) {
        }
    }
}
