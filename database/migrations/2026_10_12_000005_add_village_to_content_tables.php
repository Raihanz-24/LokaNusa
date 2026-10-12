<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['destinations', 'umkm_businesses', 'accommodations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('village')->nullable()->after('region_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['destinations', 'umkm_businesses', 'accommodations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('village');
            });
        }
    }
};
