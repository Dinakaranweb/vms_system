<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligibility_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->onDelete('cascade');
            $table->string('question');
            $table->text('description')->nullable();
            $table->enum('field_type', ['text', 'textarea', 'select', 'radio', 'checkbox', 'number', 'date', 'file']);
            $table->json('options')->nullable(); // For select, radio, checkbox options
            $table->boolean('is_required')->default(true);
            $table->integer('order')->default(0);
            $table->integer('max_size')->nullable(); // For file uploads
            $table->json('validation_rules')->nullable();
            $table->timestamps();
            
            $table->index(['tender_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligibility_questions');
    }
};