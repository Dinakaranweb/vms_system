<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'application_id')) {
                $table->foreignId('application_id')->nullable()->constrained('tender_applications')->onDelete('set null')->after('tender_id');
            }
            
            if (!Schema::hasColumn('contracts', 'signed_by_vendor')) {
                $table->foreignId('signed_by_vendor')->nullable()->constrained('users')->after('status');
            }
            
            if (!Schema::hasColumn('contracts', 'vendor_signed_at')) {
                $table->timestamp('vendor_signed_at')->nullable()->after('signed_by_vendor');
            }
            
            if (!Schema::hasColumn('contracts', 'signed_by_admin')) {
                $table->foreignId('signed_by_admin')->nullable()->constrained('users')->after('vendor_signed_at');
            }
            
            if (!Schema::hasColumn('contracts', 'admin_signed_at')) {
                $table->timestamp('admin_signed_at')->nullable()->after('signed_by_admin');
            }
            
            if (!Schema::hasColumn('contracts', 'contract_documents')) {
                $table->json('contract_documents')->nullable()->after('admin_signed_at');
            }
            
            if (!Schema::hasColumn('contracts', 'termination_reason')) {
                $table->text('termination_reason')->nullable()->after('contract_documents');
            }
            
            if (!Schema::hasColumn('contracts', 'extension_logs')) {
                $table->json('extension_logs')->nullable()->after('termination_reason');
            }
            
            if (!Schema::hasColumn('contracts', 'current_extension_count')) {
                $table->integer('current_extension_count')->default(0)->after('extension_logs');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $columns = ['application_id', 'signed_by_vendor', 'vendor_signed_at', 
                       'signed_by_admin', 'admin_signed_at', 'contract_documents',
                       'termination_reason', 'extension_logs', 'current_extension_count'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('contracts', $column)) {
                    if (in_array($column, ['application_id', 'signed_by_vendor', 'signed_by_admin'])) {
                        $table->dropForeign([$column]);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};