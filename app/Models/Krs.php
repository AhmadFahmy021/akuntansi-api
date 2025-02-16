<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Krs extends Model
{
    use HasUuids;
    protected $table = 'krs';
    protected $guarded = ['id'];

    // protected $keyType = 'string'; // UUID sebagai primary key
    // public $incrementing = false; // Primary key tidak auto-increment

    // protected static function booted()
    // {
    //     static::creating(function ($kelas) {
    //         if (empty($kelas->id)) {
    //             $kelas->id = (string) Str::uuid();
    //         }
    //     });
    // }
    // Relasi ke Kelas
    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    // Relasi ke User (Mahasiswa)
    public function mahasiswa()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

