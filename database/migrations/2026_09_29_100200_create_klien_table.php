<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('klien', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->nullable()->unique();
            $table->string('nama', 180);
            $table->string('perusahaan', 180)->nullable();
            $table->string('email', 180)->nullable();
            $table->string('telepon', 40)->nullable();
            $table->string('alamat', 500)->nullable();
            $table->string('kota', 80)->nullable();
            $table->string('status', 20)->default('prospek');
            $table->string('sumber', 20)->default('manual');
            $table->string('sumber_lain', 80)->nullable();
            $table->boolean('aktif')->default(true);
            $table->unsignedBigInteger('prospek_id')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('nama');
            $table->index('aktif');
            $table->index('prospek_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('klien');
    }
};
