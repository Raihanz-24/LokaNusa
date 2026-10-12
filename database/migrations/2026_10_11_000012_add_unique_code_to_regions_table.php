<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // `code` harus unik agar operasi upsert/impor wilayah idempoten.
        // Kolom nullable: beberapa NULL diperbolehkan (wilayah manual tanpa kode).
        Schema::table('regions', function (Blueprint $table): void {
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table): void {
            $table->dropUnique(['code']);
        });
    }
};
