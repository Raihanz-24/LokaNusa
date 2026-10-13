<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyimpan hasil itinerary terstruktur yang dihasilkan AI pada pesan asisten,
 * sehingga pengguna dapat menyimpannya ke Rencana lewat tombol (tanpa memanggil
 * ulang AI). Struktur: {title, days:[{day, activities:[{start,end,title,ref,note}]}]}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_planner_messages', function (Blueprint $table): void {
            $table->json('plan')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('trip_planner_messages', function (Blueprint $table): void {
            $table->dropColumn('plan');
        });
    }
};
