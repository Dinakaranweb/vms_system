<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            // Check if column doesn't exist before adding
            if (!Schema::hasColumn('tenders', 'emda_amount')) {
                $table->decimal('emda_amount', 15, 2)->nullable()->after('estimated_value');
            }
            
            if (!Schema::hasColumn('tenders', 'technical_requirements')) {
                $table->text('technical_requirements')->nullable()->after('eligibility_criteria');
            }
            
            if (!Schema::hasColumn('tenders', 'financial_requirements')) {
                $table->text('financial_requirements')->nullable()->after('technical_requirements');
            }
            
            if (!Schema::hasColumn('tenders', 'submission_time')) {
                $table->time('submission_time')->nullable()->after('submission_deadline');
            }
            
            if (!Schema::hasColumn('tenders', 'opening_time')) {
                $table->time('opening_time')->nullable()->after('opening_date');
            }
            
            if (!Schema::hasColumn('tenders', 'opening_venue')) {
                $table->string('opening_venue')->nullable()->after('opening_time');
            }
            
            if (!Schema::hasColumn('tenders', 'pre_bid_queries')) {
                $table->json('pre_bid_queries')->nullable()->after('documents');
            }
            
            if (!Schema::hasColumn('tenders', 'corrigendum')) {
                $table->json('corrigendum')->nullable()->after('pre_bid_queries');
            }
            
            if (!Schema::hasColumn('tenders', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->after('created_by');
            }
            
            if (!Schema::hasColumn('tenders', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $columns = ['emda_amount', 'technical_requirements', 'financial_requirements', 
                       'submission_time', 'opening_time', 'opening_venue', 'pre_bid_queries', 
                       'corrigendum', 'approved_by', 'approved_at'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('tenders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};