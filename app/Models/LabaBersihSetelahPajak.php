<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabaBersihSetelahPajak extends Model
{
    use HasFactory;

    protected $table = 'laba_bersih_setelah_pajak';

    protected $fillable = [
        'perusahaan_id',
        'laba_bersih_setelah_pajak',
    ];

    protected $casts = [
        'perusahaan_id' => 'string', // Pastikan UUID di-cast sebagai string
    ];

    /**
     * Relasi ke model Perusahaan
     */
    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'perusahaan_id', 'id');
    }
}
