<?php

namespace App\Http\Controllers;

use App\Mail\SendMail;
use App\Mail\SendMailOTP;
use App\Models\OTP;
use App\Models\ProfileMahasiswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function login_mahasiswa(Request $request)
    {
        $request->validate([
            'nim' => 'required',
            'password' => 'required'
        ]);

        if (Auth::attempt(["nim" => $request->nim, "password" => $request->password])) {
            $user = Auth::user();
            $data['token'] = $user->createToken('token')->plainTextToken;
            $data['nama'] = $user->name;
            $data['nim'] = $user->nim;
            $data['email'] = $user->email;
            return response()->json([
                "success" => true,
                "message" => "Login success",
                "data" => $data
            ], 200);
        } else {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }
    }

    public function register_mahasiswa(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'nim' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required'
        ]);

        $request['password'] = Hash::make($request['password']);
        $user = User::create($validated);
        $idUser = $user['id'];
        // dd($idUser);
        ProfileMahasiswa::create(['user_id' => $idUser]);

        $data['name'] = $user->name;
        $data['nim'] = $user->nim;
        $data['email'] = $user->email;
        $kode = random_int(100000, 999999);
        // Kode email akan di kirimkan ketika register untuk OTP
        if ($user->email_verified_at == null) {
            $otp = [
                'user_id' => $user->id,
                'kode' => $kode,
                'batas' => now()->addMinute(5),
            ];
            OTP::create($otp);
            $email = [
                'title' => "Akuntansi Application Email Verification",
                'kode' => $kode,
            ];
            try {
                Mail::to($user->email)->send(new SendMailOTP($email));
                return response()->json([
                    "success" => true,
                    "message" => "Register success",
                    "data" => $data,
                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email: ' . $e->getMessage(),
                ], 500);
            }
        }
    }

    public function login_instruktur(Request $request)
    {
        $request->validate([
            'email' => 'required|email  ',
            'password' => 'required'
        ]);

        if (Auth::attempt(["email" => $request->email, "password" => $request->password])) {
            $user = Auth::user();
            $data['token'] = $user->createToken('token')->plainTextToken;
            $data['name'] = $user->name;
            $data['email'] = $user->email;
            $data['role'] = $user->role;
            return response()->json([
                "success" => true,
                "message" => "Login success",
                "data" => $data
            ], 200);
        } else {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }
    }

    public function register_instruktur(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
        ]); 

        $request['password'] = Hash::make($request['password']);
        $request['role'] = 2;
        $user = User::create($validated);

        $data['name'] = $user->name;
        $data['email'] = $user->email;
        $data['role'] = $user->role;
        $kode = random_int(100000, 999999);
        // Kode email akan di kirimkan ketika register untuk OTP
        if ($user->email_verified_at == null) {
            $otp = [
                'user_id' => $user->id,
                'kode' => $kode,
                'batas' => now()->addMinute(5),
            ];
            OTP::create($otp);
            $email = [
                'title' => "This is your OTP code valid for 5 minutes",
                'kode' => $kode,
            ];
            try {
                Mail::to($user->email)->send(new SendMailOTP($email));
                return response()->json([
                    "success" => true,
                    "message" => "Register success",
                    "data" => $data,
                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email: ' . $e->getMessage(),
                ], 500);
            }
        }
    }

    public function verifikasi(Request $request)
    {
        $otp = OTP::where('batas', '<', now()->subDays(7))->delete();
        $otp = OTP::where('kode', $request->kode)->where('status', 1)->first();
        if (!$otp) {
            return response()->json(["message" => "Kode OTP tidak ditemukan atau telah diverifikasi"], 404);
        } else if ($otp->batas == now()->isAfter($otp->batas)) {
            $otp->delete();
            return response()->json(["message" => "Kode OTP telah kadaluwarsa atau tidak valid"], 404);
        } else if ($otp->batas == now()->isBefore($otp->batas)) {
            $user = User::find($otp->user_id);
            $user->updateOrFail(['email_verified_at' => now()]);
            $otp->update(['status' => 0]);
            // $otp->delete();
            return response()->json([
                'success' => true,
                'message' => "Email berhasil di aktivasi",
            ], 200);
        }
    }

    public function resendOTP(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        $kode = random_int(100000, 999999);
        OTP::where('user_id', $user->id)->delete();
        $otp = [
            'user_id' => $user->id,
            'kode' => $kode,
            'status' => 1,
            'batas' => now()->addMinute(5),
        ];
        OTP::create($otp);
        $styleEmail = [
            'title' => 'This is your OTP code valid for 5 minutes',
            'kode' => $kode,
        ];
        Mail::to($user->email)->send(new SendMailOTP($styleEmail));
        return response()->json(
            [
                'success' => true,
                'message' => "Send Code OTP Has Successfully",
                'data' => $user,
            ]
        );
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([

            'message' => "Logout successfully",
        ]);
    }

    public function getAllData()
    {
        $users = User::all(); // Mengambil semua data dari tabel 'users'

        return response()->json([
            "success" => true,
            "message" => "Data retrieved successfully",
            "data" => $users
        ], 200);
    }

    public function getDataById(Request $request, $id)
    {
        // Validasi token
        if (!$request->bearerToken()) {
            return response()->json([
                "success" => false,
                "message" => "Token is required"
            ], 401);
        }

        // Verifikasi token dan ambil user
        $user = User::find($id); // Ambil data berdasarkan ID

        if (!$user) {
            return response()->json([
                "success" => false,
                "message" => "Data not found"
            ], 404);
        }

        return response()->json([
            "success" => true,
            "message" => "Data retrieved successfully",
            "data" => $user
        ], 200);
    }
}
