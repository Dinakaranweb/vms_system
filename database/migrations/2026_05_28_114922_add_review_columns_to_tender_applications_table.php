<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tender_applications', function (Blueprint $table) {
            // Add reviewed_by column
            if (!Schema::hasColumn('tender_applications', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null')->after('rejection_reason');
            }
            
            // Add reviewed_at column
            if (!Schema::hasColumn('tender_applications', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            
            // Add submitted_by column
            if (!Schema::hasColumn('tender_applications', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null')->after('reviewed_at');
            }
            
            // Add submitted_at column
            if (!Schema::hasColumn('tender_applications', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            }
            
            // Add admin_remarks column if not exists
            if (!Schema::hasColumn('tender_applications', 'admin_remarks')) {
                $table->text('admin_remarks')->nullable()->after('evaluation_scores');
            }
            
            // Add evaluation_scores column if not exists
            if (!Schema::hasColumn('tender_applications', 'evaluation_scores')) {
                $table->json('evaluation_scores')->nullable()->after('documents');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tender_applications', function (Blueprint $table) {
            $columns = ['reviewed_by', 'reviewed_at', 'submitted_by', 'submitted_at', 'admin_remarks', 'evaluation_scores'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('tender_applications', $column)) {
                    if (in_array($column, ['reviewed_by', 'submitted_by'])) {
                        $table->dropForeign([$column]);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};