<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_committee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['chairman', 'member', 'observer', 'technical_expert', 'financial_expert']);
            $table->string('designation')->nullable();
            $table->text('permissions')->nullable(); // JSON of permissions
            $table->boolean('is_active')->default(true);
            $table->foreignId('added_by')->constrained('users');
            $table->timestamps();
            
            $table->unique(['tender_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_committee');
    }
};