<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tender_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('tender_applications', 'postal_quote_scan')) {
                $table->text('postal_quote_scan')->nullable()->after('financial_proposal_file');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tender_applications', function (Blueprint $table) {
            if (Schema::hasColumn('tender_applications', 'postal_quote_scan')) {
                $table->dropColumn('postal_quote_scan');
            }
        });
    }
};
