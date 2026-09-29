<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagihan_bayar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->date('tgl');
            $table->decimal('nominal', 14, 2);
            $table->string('metode', 20)->default('transfer');
            $table->string('referensi', 120)->nullable();
            $table->string('catatan', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tagihan_id', 'tgl'], 'tagihan_bayar_tagihan_tgl_index');
        });

        Schema::create('lampiran', function (Blueprint $table) {
            $table->id();
            $table->string('entitas', 20);
            $table->unsignedBigInteger('entitas_id');
            $table->string('nama_asli', 200);
            $table->string('path', 255);
            $table->unsignedInteger('ukuran');
            $table->string('mime', 100);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['entitas', 'entitas_id'], 'lampiran_entitas_index');
        });

        Schema::create('template_tahapan', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20);
            $table->unsignedSmallInteger('urutan');
            $table->string('nama', 100);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['jenis', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_tahapan');
        Schema::dropIfExists('lampiran');
        Schema::dropIfExists('tagihan_bayar');
    }
};
