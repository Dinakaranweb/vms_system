<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // User information
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_role')->nullable();
            
            // Action information
            $table->string('action'); // create, update, delete, approve, reject, login, logout, etc.
            $table->string('module'); // vendor, tender, application, contract, user, etc.
            $table->string('record_id')->nullable(); // ID of the affected record
            $table->string('record_identifier')->nullable(); // Name/title/code of the record
            
            // Data changes
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('changes')->nullable();
            
            // Request information
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method')->nullable();
            
            // Additional context
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes for faster queries
            $table->index(['action', 'module']);
            $table->index(['user_id', 'created_at']);
            $table->index('module');
            $table->index('record_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};