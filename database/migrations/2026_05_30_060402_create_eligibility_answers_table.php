<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligibility_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_application_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained('eligibility_questions')->onDelete('cascade');
            $table->text('answer')->nullable();
            $table->string('file_path')->nullable(); // For file uploads
            $table->boolean('is_eligible')->default(true);
            $table->timestamps();
            
            $table->unique(['tender_application_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligibility_answers');
    }
};