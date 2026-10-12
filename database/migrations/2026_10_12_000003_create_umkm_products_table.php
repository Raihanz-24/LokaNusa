<?php

use App\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umkm_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('umkm_business_id')->constrained('umkm_businesses')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price')->nullable();
            $table->string('unit')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_available')->default(true);
            $table->string('status')->default(ContentStatus::Published->value)->index();
            $table->timestamps();

            $table->unique(['umkm_business_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_products');
    }
};
