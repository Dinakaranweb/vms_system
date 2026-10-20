<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who keyed in a bid value, and when.
 *
 * For a postal tender the price is not submitted through the portal at all — it is read off
 * the physical quote in the envelope and typed in at the opening table. That makes the price
 * the one figure in the system with no vendor-side provenance, so record the provenance we do
 * have: the officer or committee member who entered it, and the moment they did.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tender_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('tender_applications', 'bid_recorded_by')) {
                $table->foreignId('bid_recorded_by')->nullable()->after('bid_amount')
                      ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('tender_applications', 'bid_recorded_at')) {
                $table->timestamp('bid_recorded_at')->nullable()->after('bid_recorded_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tender_applications', function (Blueprint $table) {
            if (Schema::hasColumn('tender_applications', 'bid_recorded_by')) {
                $table->dropForeign(['bid_recorded_by']);
                $table->dropColumn('bid_recorded_by');
            }
            if (Schema::hasColumn('tender_applications', 'bid_recorded_at')) {
                $table->dropColumn('bid_recorded_at');
            }
        });
    }
};
