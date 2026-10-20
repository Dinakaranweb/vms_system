<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Purchase Officer's record of finalizing an award.
 *
 * The committee's recommendations are advisory, so nothing in the sequential sign-off chain
 * gates an award any more. What does gate it is this: the officer must state, on the record,
 * which applicant was chosen, that a discussion with the committee took place, whether it
 * happened online or orally, who took part, and why. The decision is the officer's — this
 * table is what makes it accountable, and it is the gate ApplicationController and
 * TenderOwnerApplicationController check before any contract can be issued.
 *
 * A finalization can be revoked while no contract has been issued yet, which is why the choice
 * is not simply a column on the application.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('award_finalizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->onDelete('cascade');
            $table->foreignId('application_id')->constrained('tender_applications')->onDelete('cascade');
            $table->foreignId('finalized_by')->constrained('users');

            // How the officer consulted the committee before deciding.
            $table->enum('discussion_mode', ['online', 'oral']);
            $table->timestamp('discussion_held_at')->nullable();
            // Committee member user ids that took part, as recorded by the officer.
            $table->json('participants')->nullable();

            // Snapshot of the advisory tally at the moment of the decision, so later edits to
            // committee comments cannot rewrite what the officer was looking at.
            $table->json('committee_summary')->nullable();

            $table->text('decision_note');
            $table->decimal('awarded_value', 15, 2)->nullable();

            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revoked_reason')->nullable();

            $table->timestamps();

            $table->index(['tender_id', 'revoked_at'], 'af_tender_active_idx');
            $table->index('application_id', 'af_application_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('award_finalizations');
    }
};
