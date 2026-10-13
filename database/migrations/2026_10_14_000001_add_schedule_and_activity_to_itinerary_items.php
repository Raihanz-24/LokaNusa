<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Memperkaya butir rencana perjalanan:
 * - jam mulai & selesai per kegiatan (timeline harian),
 * - dukungan kegiatan bebas (teks) tanpa tautan konten → kolom morph dibuat
 *   nullable + kolom `title` & `location` untuk kegiatan manual/hasil AI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_items', function (Blueprint $table): void {
            // Kegiatan bebas (mis. hasil itinerary AI) tidak menautkan konten.
            $table->string('item_type')->nullable()->change();
            $table->unsignedBigInteger('item_id')->nullable()->change();

            $table->string('title')->nullable()->after('item_id');
            $table->string('location')->nullable()->after('title');

            $table->time('start_time')->nullable()->after('sort_order');
            $table->time('end_time')->nullable()->after('start_time');
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_items', function (Blueprint $table): void {
            $table->dropColumn(['title', 'location', 'start_time', 'end_time']);

            $table->string('item_type')->nullable(false)->change();
            $table->unsignedBigInteger('item_id')->nullable(false)->change();
        });
    }
};
