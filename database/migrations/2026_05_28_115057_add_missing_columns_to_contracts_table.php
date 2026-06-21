<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Add payment_terms column
            if (!Schema::hasColumn('contracts', 'payment_terms')) {
                $table->text('payment_terms')->nullable()->after('contract_value');
            }
            
            // Add deliverables column
            if (!Schema::hasColumn('contracts', 'deliverables')) {
                $table->text('deliverables')->nullable()->after('payment_terms');
            }
            
            // Add terms_and_conditions column
            if (!Schema::hasColumn('contracts', 'terms_and_conditions')) {
                $table->text('terms_and_conditions')->nullable()->after('deliverables');
            }
            
            // Add signed_by_vendor column
            if (!Schema::hasColumn('contracts', 'signed_by_vendor')) {
                $table->foreignId('signed_by_vendor')->nullable()->constrained('users')->after('status');
            }
            
            // Add vendor_signed_at column
            if (!Schema::hasColumn('contracts', 'vendor_signed_at')) {
                $table->timestamp('vendor_signed_at')->nullable()->after('signed_by_vendor');
            }
            
            // Add signed_by_admin column
            if (!Schema::hasColumn('contracts', 'signed_by_admin')) {
                $table->foreignId('signed_by_admin')->nullable()->constrained('users')->after('vendor_signed_at');
            }
            
            // Add admin_signed_at column
            if (!Schema::hasColumn('contracts', 'admin_signed_at')) {
                $table->timestamp('admin_signed_at')->nullable()->after('signed_by_admin');
            }
            
            // Add contract_documents column
            if (!Schema::hasColumn('contracts', 'contract_documents')) {
                $table->json('contract_documents')->nullable()->after('admin_signed_at');
            }
            
            // Add termination_reason column
            if (!Schema::hasColumn('contracts', 'termination_reason')) {
                $table->text('termination_reason')->nullable()->after('contract_documents');
            }
            
            // Add extension_logs column
            if (!Schema::hasColumn('contracts', 'extension_logs')) {
                $table->json('extension_logs')->nullable()->after('termination_reason');
            }
            
            // Add current_extension_count column
            if (!Schema::hasColumn('contracts', 'current_extension_count')) {
                $table->integer('current_extension_count')->default(0)->after('extension_logs');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $columns = ['payment_terms', 'deliverables', 'terms_and_conditions', 
                       'signed_by_vendor', 'vendor_signed_at', 'signed_by_admin', 
                       'admin_signed_at', 'contract_documents', 'termination_reason', 
                       'extension_logs', 'current_extension_count'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('contracts', $column)) {
                    if (in_array($column, ['signed_by_vendor', 'signed_by_admin'])) {
                        $table->dropForeign([$column]);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};