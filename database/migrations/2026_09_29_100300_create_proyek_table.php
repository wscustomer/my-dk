<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyek', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klien_id')->constrained('klien')->cascadeOnDelete();
            $table->string('kode', 30)->nullable()->unique();
            $table->string('nama', 150);
            $table->string('jenis', 20)->default('website');
            $table->text('deskripsi')->nullable();
            $table->string('status', 20)->default('penawaran');
            $table->decimal('nilai_kontrak', 14, 2)->default(0);
            $table->decimal('dp_nominal', 14, 2)->default(0);
            $table->date('tgl_mulai')->nullable();
            $table->date('tgl_target')->nullable();
            $table->date('tgl_serah')->nullable();
            $table->foreignId('pemilik_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('urutan_papan')->default(0);
            $table->string('portal_token', 64)->nullable()->unique();
            $table->unsignedSmallInteger('revisi')->default(0);
            $table->string('tautan_hasil', 255)->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('jenis');
            $table->index('tgl_target');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyek');
    }
};
