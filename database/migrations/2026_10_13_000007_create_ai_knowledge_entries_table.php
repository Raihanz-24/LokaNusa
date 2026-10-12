<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            // Kata kunci pemicu, dipisah koma (mis. "developer, pembuat, siapa yang buat").
            $table->text('keywords');
            // Jawaban yang diberikan bila salah satu kata kunci cocok.
            $table->text('answer');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_entries');
    }
};
