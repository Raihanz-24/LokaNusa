<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifikasi dalam aplikasi (in-app) untuk pengguna publik LokaNusa.
 *
 * Terpisah dari tabel `notifications` bawaan Laravel agar tidak berbenturan
 * dengan kanal mail milik fitur autentikasi, dan agar payload notifikasi
 * (judul, isi, tautan, ikon, kategori) mudah dikustom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // moderation | event | highlight | ai
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Daftar notifikasi milik pengguna, belum dibaca lebih dulu.
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
