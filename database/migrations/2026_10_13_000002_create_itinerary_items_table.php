<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itinerary_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('itinerary_id')->constrained()->cascadeOnDelete();
            // item_type menyimpan nama kelas model (destinasi/UMKM/penginapan/event).
            $table->morphs('item');
            $table->unsignedSmallInteger('day')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['itinerary_id', 'item_type', 'item_id'], 'itinerary_items_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itinerary_items');
    }
};
