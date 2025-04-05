<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jurnal extends Model
{
    use HasUuids;
    protected $guarded = ['id'];
    protected $table = 'jurnal';

    /**
     * Get the akun that owns the Jurnal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'akun_id', 'id');
    }

    /**
     * Get the subAkun that owns the Jurnal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subAkun(): BelongsTo
    {
        return $this->belongsTo(SubAkun::class, 'sub_akun_id', 'id');
    }

    /**
     * Get the perusahaan that owns the Jurnal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class, 'perusahaan_id', 'id');
    }
}
