<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            if (!Schema::hasColumn('tenders', 'require_contract_signing')) {
                $table->boolean('require_contract_signing')->default(true)->after('require_financial_bid');
            }
            if (!Schema::hasColumn('tenders', 'notify_vendors_on_create')) {
                $table->boolean('notify_vendors_on_create')->default(false)->after('require_contract_signing');
            }
            if (!Schema::hasColumn('tenders', 'notify_specific_vendors')) {
                $table->json('notify_specific_vendors')->nullable()->after('notify_vendors_on_create');
            }
        });

        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'supporting_documents')) {
                $table->json('supporting_documents')->nullable()->after('order_generated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn(['require_contract_signing', 'notify_vendors_on_create', 'notify_specific_vendors']);
        });
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('supporting_documents');
        });
    }
};
