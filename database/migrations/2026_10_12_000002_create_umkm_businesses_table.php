<?php

use App\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umkm_businesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('business_type')->index();
            $table->text('description')->nullable();

            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone')->nullable();

            $table->json('opening_hours')->nullable();
            $table->json('gallery')->nullable();

            $table->string('status')->default(ContentStatus::Draft->value)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['business_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_businesses');
    }
};
