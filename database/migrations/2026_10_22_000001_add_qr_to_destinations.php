<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * QR Smart Tourism: token QR unik per destinasi + catatan pemindaian.
 *
 * QR mengarah ke URL publik `/q/{token}` yang mencatat pemindaian lalu
 * mengalihkan pengunjung ke halaman detail destinasi. Tidak ada data palsu;
 * statistik dihitung dari pemindaian nyata.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destinations', function (Blueprint $table): void {
            $table->string('qr_token', 32)->nullable()->unique()->after('slug');
        });

        // Backfill token untuk destinasi yang sudah ada (idempoten).
        $existing = DB::table('destinations')
            ->whereNull('qr_token')
            ->pluck('id');

        foreach ($existing as $id) {
            do {
                $token = Str::lower(Str::random(16));
            } while (DB::table('destinations')->where('qr_token', $token)->exists());

            DB::table('destinations')
                ->where('id', $id)
                ->update(['qr_token' => $token]);
        }

        Schema::create('qr_scans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index(['destination_id', 'scanned_at']);
            $table->index(['user_id', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_scans');

        Schema::table('destinations', function (Blueprint $table): void {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
