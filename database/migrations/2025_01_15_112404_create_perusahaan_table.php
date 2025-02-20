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
        Schema::create('perusahaan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->text('alamat');
            $table->integer('tahun_berdiri');
            $table->date('start_priode');
            $table->date('end_priode');
            $table->string('status', 100)->default('offline');
            $table->foreignUuid('kategori_id')->constrained('kategori')->cascadeOnDelete();
            // $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('krs_id')->constrained('krs')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perusahaan');
    }
};
