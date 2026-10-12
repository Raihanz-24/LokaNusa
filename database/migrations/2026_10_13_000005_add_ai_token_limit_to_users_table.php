<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Batas token AI per hari untuk pengguna ini (null = tanpa batas).
            // Dapat diatur admin; sengaja nullable agar default tidak dibatasi.
            $table->unsignedBigInteger('ai_token_limit')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('ai_token_limit');
        });
    }
};
