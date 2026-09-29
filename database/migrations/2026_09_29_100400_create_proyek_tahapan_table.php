<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyek_tahapan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyek_id')->constrained('proyek')->cascadeOnDelete();
            $table->string('nama', 120);
            $table->unsignedSmallInteger('urutan');
            $table->string('status', 20)->default('belum');
            $table->date('tgl_mulai')->nullable();
            $table->date('tgl_selesai')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['proyek_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyek_tahapan');
    }
};
