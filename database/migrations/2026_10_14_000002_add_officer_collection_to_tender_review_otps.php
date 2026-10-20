<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opening codes are now keyed in by the Purchase Officer on a single screen — each committee
 * member reads their own code (delivered to their in-app notifications) out to the officer in
 * person. These columns keep the audit trail that move would otherwise lose: when a member's
 * code was issued, and which officer actually entered it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tender_review_otps', function (Blueprint $table) {
            if (!Schema::hasColumn('tender_review_otps', 'otp_sent_at')) {
                $table->timestamp('otp_sent_at')->nullable()->after('otp_expires_at');
            }
            if (!Schema::hasColumn('tender_review_otps', 'otp_collected_by')) {
                $table->foreignId('otp_collected_by')->nullable()->after('otp_verified_at')
                      ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tender_review_otps', function (Blueprint $table) {
            if (Schema::hasColumn('tender_review_otps', 'otp_collected_by')) {
                $table->dropForeign(['otp_collected_by']);
                $table->dropColumn('otp_collected_by');
            }
            if (Schema::hasColumn('tender_review_otps', 'otp_sent_at')) {
                $table->dropColumn('otp_sent_at');
            }
        });
    }
};
