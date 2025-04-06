<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class Keuangan extends Model
{
    use HasUuids, HasFactory;
    protected $table = 'keuangan';
    protected $guarded = ['id'];

    /**
     * Get the akun that owns the Keuangan
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'akun_id', 'id');
    }

    /**
     * Get the perusahaan that owns the Keuangan
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class, 'perusahaan_id', 'id');
    }

    /**
     * Get the sub_akun that owns the Keuangan
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sub_akun(): BelongsTo
    {
        return $this->belongsTo(SubAkun::class, 'sub_akun_id', 'id');
    }

}
