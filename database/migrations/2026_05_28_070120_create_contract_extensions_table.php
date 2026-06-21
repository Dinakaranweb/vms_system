<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_extensions', function (Blueprint $table) {
            $table->id();
            
            // Contract reference
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('contract_number')->nullable();
            
            // Extension details
            $table->integer('extension_number'); // 1st, 2nd, 3rd extension
            $table->date('previous_end_date');
            $table->date('new_end_date');
            $table->integer('extension_days')->nullable(); // Number of days extended
            
            // Financial details
            $table->decimal('additional_value', 15, 2)->nullable();
            $table->decimal('previous_contract_value', 15, 2)->nullable();
            $table->decimal('new_contract_value', 15, 2)->nullable();
            
            // Terms and conditions
            $table->text('reason_for_extension')->nullable();
            $table->json('terms_changes')->nullable();
            $table->json('modified_clauses')->nullable();
            
            // Documents
            $table->string('extension_document')->nullable(); // Path to extension agreement document
            $table->json('supporting_documents')->nullable();
            
            // Approval workflow
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users');
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Notifications
            $table->boolean('vendor_notified')->default(false);
            $table->timestamp('vendor_notified_at')->nullable();
            $table->boolean('admin_notified')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['contract_id', 'extension_number']);
            $table->index('status');
            $table->index('new_end_date');
            $table->index('approved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_extensions');
    }
};