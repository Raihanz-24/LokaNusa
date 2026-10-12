<?php

use App\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destinations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('destination_category_id')->nullable()->constrained('destination_categories')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status')->default(ContentStatus::Draft->value)->index();
            $table->timestamp('published_at')->nullable()->index();

            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Jam operasional: [{'day': 'senin', 'open': '08:00', 'close': '17:00', 'closed': false}, ...]
            $table->json('opening_hours')->nullable();
            // Fasilitas: ['Parkir', 'Toilet', ...]
            $table->json('facilities')->nullable();
            // Galeri: ['destinations/xxx.jpg', ...]
            $table->json('gallery')->nullable();

            $table->unsignedBigInteger('ticket_price')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['destination_category_id', 'status']);
            $table->index(['region_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destinations');
    }
};
