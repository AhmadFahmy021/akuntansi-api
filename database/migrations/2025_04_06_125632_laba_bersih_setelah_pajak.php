<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('laba_bersih_setelah_pajak', function (Blueprint $table) {
            $table->id();
            $table->uuid('perusahaan_id'); // Ubah dari foreignId ke uuid
            $table->decimal('laba_bersih_setelah_pajak', 15, 2);
            $table->timestamps();
        
            // Tambahkan Foreign Key
            $table->foreign('perusahaan_id')->references('id')->on('perusahaan')->onDelete('cascade');
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laba_bersih_setelah_pajak');
    }
};
