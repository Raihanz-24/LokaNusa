<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estimasi biaya per kegiatan pada rencana perjalanan.
 *
 * `cost` = perkiraan biaya (Rupiah, bilangan bulat). Bila kegiatan menautkan
 * konten (destinasi/penginapan/event) dan `cost` kosong, biaya diturunkan dari
 * harga konten (tiket/harga per malam). `cost_note` menjelaskan satuan biaya
 * (mis. "per orang", "per malam").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('cost')->nullable()->after('end_time');
            $table->string('cost_note')->nullable()->after('cost');
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_items', function (Blueprint $table): void {
            $table->dropColumn(['cost', 'cost_note']);
        });
    }
};
