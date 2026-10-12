<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('type')->index();
            $table->string('name');
            $table->string('slug');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            // Mencegah duplikasi slug di bawah induk yang sama.
            // (NULL parent = level teratas; MySQL memperbolehkan beberapa NULL.)
            $table->unique(['parent_id', 'slug']);
            $table->index(['type', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
