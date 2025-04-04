<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ProfileMahasiswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileMahasiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $profile = ProfileMahasiswa::with(['user'])->where('user_id', Auth::user()->id)->first();
        return response()->json([
            'success' => true,
            'data' => $profile
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bio' => 'nullable|string',
            'alamat' => 'nullable|string',
            'gender' => 'nullable|string',
            'tempat' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'foto' => 'mimes:jpg,png,jpeg|max:3048',
            'hp' => 'nullable|numeric',
            'intagram' => 'nullable',
            'tiktok' => 'nullable',
            'facebook' => 'nullable',
        ]);
        $validated['user_id'] = Auth::user()->id;
        if ($request->hasFile("foto") && $request->file('foto')->isValid()) {
            $validated['foto'] = $request->file('foto')->store('profiles', 'public');
        }
        // dd($validated);
        ProfileMahasiswa::create($validated);
        return response()->json([
            'success' => true,
            'message' => 'Data Successfully Saved',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $profileMahasiswa)
    {
        // dd($profileMahasiswa);
        // try {
            // $user = User::findOrFail(Auth::user()->id);
            $profile = ProfileMahasiswa::with(['user'])->where('user_id', Auth::user()->id);
            return response()->json([
                'success' => true,
                'data' => $profile
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => true,
        //         'message' => 'Data Not Found'
        //     ], 200);
        // }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $profileMahasiswa)
    {
        // try {
            $profile = ProfileMahasiswa::findOrFail($profileMahasiswa);
            $validated = $request->validate([
                'user_id' => 'sometimes|uuid',
                'bio' => 'sometimes|string',
                'gender' => 'sometimes|string',
                'tempat' => 'sometimes|string',
                'tanggal_lahir' => 'sometimes|date',
                'alamat' => 'sometimes|string',
                'foto' => 'mimes:jpg,png,jpeg|max:3048',
                'hp' => 'sometimes|numeric',
                'intagram' => 'nullable',
                'tiktok' => 'nullable',
                'facebook' => 'nullable',
            ]);
            if ($request->hasFile("foto") && $request->file('foto')->isValid()) {
                Storage::disk('public')->delete($profile->foto);
                $validated['foto'] = $request->file('foto')->store('profiles', 'public');
            }
            $profile->update($validated);
            return response()->json([
                'success' => true,
                'message' => 'Data Successfully Chaged',
                'data' => $profile
            ], 200);
        // } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Data Failed Chaged',
            ], 404);
        // }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $profileMahasiswa)
    {
        try {
            $profile = ProfileMahasiswa::findOrFail($profileMahasiswa);
            if (!empty($profile->foto)) Storage::disk('public')->delete($profile->foto);
            $profile->delete();
            // dd($profile);
            return response()->json([
                'success' => true,
                'message' => 'Data Successfully Deleted',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Data Failed Delete',
            ], 404);
        }
    }
}