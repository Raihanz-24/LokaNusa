<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Generalisasi QR Smart Tourism ke semua jenis konten.
 *
 * Sebelumnya token QR hanya ada di `destinations.qr_token` dan pemindaian
 * menunjuk langsung ke `destination_id`. Migrasi ini memindahkan token ke
 * tabel terpusat `qr_codes` (polimorfik) dan mengubah `qr_scans` agar
 * merujuk `qr_code_id`, sehingga UMKM, penginapan, budaya, dan event bisa
 * memakai mekanisme yang sama.
 *
 * Data lama tetap dipertahankan: token destinasi disalin ke `qr_codes` dan
 * pemindaian lama dipetakan ke kode barunya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('token', 32)->unique();
            $table->morphs('qr_codeable');
            $table->timestamps();

            $table->unique(['qr_codeable_type', 'qr_codeable_id'], 'qr_codes_target_unique');
        });

        // Tambah kolom penghubung pada qr_scans (masih nullable saat backfill).
        Schema::table('qr_scans', function (Blueprint $table): void {
            $table->foreignId('qr_code_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Backfill token destinasi yang sudah ada.
        $destinations = DB::table('destinations')
            ->whereNotNull('qr_token')
            ->get(['id', 'qr_token']);

        foreach ($destinations as $destination) {
            $qrCodeId = DB::table('qr_codes')->insertGetId([
                'token' => $destination->qr_token,
                'qr_codeable_type' => 'App\\Models\\Destination',
                'qr_codeable_id' => $destination->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('qr_scans')
                ->where('destination_id', $destination->id)
                ->update(['qr_code_id' => $qrCodeId]);
        }

        // Pemindaian yang destinasi tokennya kosong (tidak mungkin terjadi,
        // tetapi dijaga agar migrasi tidak gagal) dipindahkan ke token baru.
        $orphanDestinationIds = DB::table('qr_scans')
            ->whereNull('qr_code_id')
            ->distinct()
            ->pluck('destination_id');

        foreach ($orphanDestinationIds as $destinationId) {
            $qrCodeId = DB::table('qr_codes')->insertGetId([
                'token' => Str::lower(Str::random(16)),
                'qr_codeable_type' => 'App\\Models\\Destination',
                'qr_codeable_id' => $destinationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('qr_scans')
                ->where('destination_id', $destinationId)
                ->whereNull('qr_code_id')
                ->update(['qr_code_id' => $qrCodeId]);
        }

        Schema::table('qr_scans', function (Blueprint $table): void {
            $table->dropForeign(['destination_id']);
            $table->dropIndex(['destination_id', 'scanned_at']);
            $table->dropColumn('destination_id');
        });

        Schema::table('qr_scans', function (Blueprint $table): void {
            $table->index(['qr_code_id', 'scanned_at']);
        });

        Schema::table('destinations', function (Blueprint $table): void {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }

    public function down(): void
    {
        Schema::table('destinations', function (Blueprint $table): void {
            $table->string('qr_token', 32)->nullable()->unique()->after('slug');
        });

        Schema::table('qr_scans', function (Blueprint $table): void {
            $table->foreignId('destination_id')->nullable()->after('id')
                ->constrained()->cascadeOnDelete();
        });

        $codes = DB::table('qr_codes')
            ->where('qr_codeable_type', 'App\\Models\\Destination')
            ->get();

        foreach ($codes as $code) {
            DB::table('destinations')
                ->where('id', $code->qr_codeable_id)
                ->update(['qr_token' => $code->token]);

            DB::table('qr_scans')
                ->where('qr_code_id', $code->id)
                ->update(['destination_id' => $code->qr_codeable_id]);
        }

        Schema::table('qr_scans', function (Blueprint $table): void {
            $table->dropForeign(['qr_code_id']);
            $table->dropColumn('qr_code_id');
        });

        Schema::dropIfExists('qr_codes');
    }
};
