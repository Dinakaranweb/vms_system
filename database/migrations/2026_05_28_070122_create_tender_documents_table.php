<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_documents', function (Blueprint $table) {
            $table->id();
            
            // Tender reference
            $table->foreignId('tender_id')->constrained()->onDelete('cascade');
            $table->string('tender_number')->nullable();
            
            // Document information
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type'); // mime type
            $table->integer('file_size'); // in bytes
            $table->string('file_extension');
            $table->string('document_hash')->nullable(); // For file integrity
            
            // Document categorization
            $table->enum('document_category', [
                'tender_notice',
                'bidding_document',
                'corrigendum',
                'annexure',
                'specification',
                'terms_conditions',
                'boq', // Bill of Quantities
                'drawing',
                'clarification',
                'pre_bid_meeting_minutes',
                'other'
            ])->default('other');
            
            // Document properties
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_confidential')->default(false);
            $table->boolean('is_public')->default(true);
            $table->integer('version')->default(1);
            $table->string('language')->default('en');
            
            // For corrigendum tracking
            $table->foreignId('previous_version_id')->nullable()->constrained('tender_documents');
            $table->text('change_notes')->nullable();
            $table->boolean('is_latest')->default(true);
            
            // Upload information
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamp('uploaded_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            
            // Access control
            $table->boolean('requires_signature')->default(false);
            $table->boolean('is_signed')->default(false);
            $table->string('signature_path')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['tender_id', 'document_category']);
            $table->index('document_category');
            $table->index('is_mandatory');
            $table->index('is_latest');
            $table->index('version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_documents');
    }
};