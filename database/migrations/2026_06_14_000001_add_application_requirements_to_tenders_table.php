<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            if (!Schema::hasColumn('tenders', 'require_bid_amount')) {
                $table->boolean('require_bid_amount')->default(true)->after('eligibility_criteria');
            }
            if (!Schema::hasColumn('tenders', 'require_technical_bid')) {
                $table->boolean('require_technical_bid')->default(true)->after('require_bid_amount');
            }
            if (!Schema::hasColumn('tenders', 'require_financial_bid')) {
                $table->boolean('require_financial_bid')->default(true)->after('require_technical_bid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn(['require_bid_amount', 'require_technical_bid', 'require_financial_bid']);
        });
    }
};
