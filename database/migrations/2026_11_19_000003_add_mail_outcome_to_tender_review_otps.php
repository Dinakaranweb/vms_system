<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records what the mail server said when a holder's opening code was sent.
 *
 * Until now a send that the relay refused was written to the log and nowhere else, so the
 * officer keying in codes had no way to tell "the code is in their inbox" from "the code never
 * left the building". These columns keep the answer next to the code it belongs to.
 *
 * mail_status is deliberately not called anything like "delivered": the only thing the SMTP
 * conversation proves is that the relay took the message. A relay can accept an address and
 * bounce it asynchronously minutes later, which is exactly what Gmail does for an address at a
 * domain that resolves but has no mailbox.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tender_review_otps', function (Blueprint $table) {
            // accepted | rejected | error | no-address
            $table->string('mail_status', 20)->nullable()->after('otp_sent_at');
            // The mail server's own words, scrubbed of the AUTH exchange before it is stored.
            $table->text('mail_response')->nullable()->after('mail_status');
            // The relay's queue id, which is what Workspace support asks for to trace a message.
            $table->string('mail_message_id', 255)->nullable()->after('mail_response');
            $table->timestamp('mail_attempted_at')->nullable()->after('mail_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('tender_review_otps', function (Blueprint $table) {
            $table->dropColumn(['mail_status', 'mail_response', 'mail_message_id', 'mail_attempted_at']);
        });
    }
};
