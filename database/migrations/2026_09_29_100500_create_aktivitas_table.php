<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aktivitas', function (Blueprint $table) {
            $table->id();
            $table->string('entitas', 20);
            $table->unsignedBigInteger('entitas_id');
            $table->foreignId('klien_id')->nullable()->constrained('klien')->nullOnDelete();
            $table->foreignId('proyek_id')->nullable()->constrained('proyek')->nullOnDelete();
            $table->string('jenis', 30)->default('catatan');
            $table->string('judul', 200);
            $table->text('catatan')->nullable();
            $table->string('hasil', 60)->nullable();
            $table->date('tgl');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['entitas', 'entitas_id'], 'aktivitas_entitas_index');
            $table->index('tgl');
            $table->index('klien_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aktivitas');
    }
};
