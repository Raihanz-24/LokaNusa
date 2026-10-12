<?php

use App\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organizer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('event_type')->index();
            $table->text('description')->nullable();

            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->nullable();

            $table->string('location_name')->nullable();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->boolean('is_free')->default(true);
            $table->unsignedInteger('ticket_price')->nullable();
            $table->string('organizer_name')->nullable();
            $table->string('contact')->nullable();

            $table->string('cover_image')->nullable();
            $table->json('gallery')->nullable();

            $table->string('status')->default(ContentStatus::Draft->value)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->text('rejection_reason')->nullable();

            $table->boolean('is_recommended')->default(false)->index();
            $table->unsignedInteger('recommendation_order')->default(0);

            $table->timestamps();

            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
