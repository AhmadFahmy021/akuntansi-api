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
        Schema::create('keuangan', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->foreignUuid("akun_id")->constrained("akun")->cascadeOnDelete();
            $table->foreignUuid("perusahaan_id")->constrained("perusahaan")->cascadeOnDelete();
            $table->integer("debit")->nullable();
            $table->integer("kredit")->nullable();
            $table->foreignUuid("sub_akun_id")->nullabl()->constrained("sub_akun")->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keuangan');
    }
};
