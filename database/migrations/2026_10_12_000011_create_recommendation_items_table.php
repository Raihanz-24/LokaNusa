<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_items', function (Blueprint $table): void {
            $table->id();
            $table->string('recommendable_type');
            $table->unsignedBigInteger('recommendable_id');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['recommendable_type', 'recommendable_id'], 'recommendation_items_unique');
            $table->index(['recommendable_type', 'recommendable_id'], 'recommendation_items_morph_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_items');
    }
};
