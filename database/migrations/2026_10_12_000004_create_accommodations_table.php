<?php

use App\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accommodations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->index();
            $table->text('description')->nullable();

            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone')->nullable();

            $table->json('amenities')->nullable();
            $table->json('gallery')->nullable();
            $table->string('check_in_time')->nullable();
            $table->string('check_out_time')->nullable();
            $table->unsignedBigInteger('price_per_night')->nullable();

            $table->string('status')->default(ContentStatus::Draft->value)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodations');
    }
};
