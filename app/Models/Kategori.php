<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Kategori extends Model
{
    use HasUuids;
    // Menentukan nama tabel (jika tabelnya tidak otomatis sesuai dengan nama model)
    protected $table = 'kategori';

    // Menentukan kolom-kolom yang bisa diisi (fillable)
    protected $guarded = ['id'];

    // // Mengatur agar UUID digunakan sebagai primary key dan tidak auto-increment
    // protected $keyType = 'string';
    // public $incrementing = false;

    // // Menangani UUID secara otomatis saat membuat kategori baru
    // protected static function booted()
    // {
    //     static::creating(function ($kategori) {
    //         if (empty($kategori->id)) {
    //             $kategori->id = (string) Str::uuid();
    //         }
    //     });
    // }
}
