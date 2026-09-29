<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klien_id')->constrained('klien')->cascadeOnDelete();
            $table->foreignId('proyek_id')->nullable()->constrained('proyek')->nullOnDelete();
            $table->string('judul', 180);
            $table->decimal('total', 14, 2);
            $table->decimal('terbayar_total_hitung', 14, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->date('tgl_terbit')->nullable();
            $table->date('tgl_jatuh_tempo')->nullable();
            $table->date('tgl_bayar')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('tgl_jatuh_tempo');
            $table->index('klien_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan');
    }
};
