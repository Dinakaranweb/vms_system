<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Two changes that belong together, because both concern who is entitled to act on a tender.
 *
 * 1. An extra committee member invited onto one particular tender is no longer simply added.
 *    The request goes to the main Purchase Committee, in the Super Admin's approval_order,
 *    and the person joins the tender only once the last main member has approved. So a
 *    tender_committee row now carries a status, and tender_committee_approvals records one
 *    step per main member — the same shape as vendor_approvals.
 *
 * 2. Opening codes are no longer held only by main committee members. On a tender created by
 *    a Tender Owner the owner starts the opening and the Purchase Officer holds a code
 *    instead, and approved add-on members hold one too. holder_role records which of those a
 *    code was issued for, so the opening screen can say why each person is on the list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tender_committee', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('is_active');
            $table->foreignId('requested_by')->nullable()->after('added_by')
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable()->after('requested_by');
            $table->text('decision_reason')->nullable()->after('decided_at');

            $table->index(['tender_id', 'status']);
        });

        // Anyone already sitting on a tender predates the approval requirement and keeps
        // their place; only additions made from here on have to be approved.
        DB::table('tender_committee')->update(['status' => 'approved', 'decided_at' => now()]);

        Schema::create('tender_committee_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_committee_id')->constrained('tender_committee')->cascadeOnDelete();
            $table->foreignId('committee_member_id')->constrained('users')->cascadeOnDelete();

            // Copied from purchase_committee_members when the request is raised, so that
            // reordering the committee later cannot rewrite a decision already taken.
            $table->unsignedInteger('approval_order');
            $table->string('designation')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['tender_committee_id', 'committee_member_id'], 'tca_request_member_unique');
            $table->index(['tender_committee_id', 'approval_order'], 'tca_request_order_index');
            $table->index(['committee_member_id', 'status'], 'tca_member_status_index');
        });

        Schema::table('tender_review_otps', function (Blueprint $table) {
            // 'main' | 'officer' | 'add-on'. Nullable because every row that exists now was
            // issued to a main committee member, back when that was the only possibility.
            $table->string('holder_role', 20)->nullable()->after('committee_member_id');
        });

        DB::table('tender_review_otps')->whereNull('holder_role')->update(['holder_role' => 'main']);
    }

    public function down(): void
    {
        Schema::table('tender_review_otps', function (Blueprint $table) {
            $table->dropColumn('holder_role');
        });

        Schema::dropIfExists('tender_committee_approvals');

        Schema::table('tender_committee', function (Blueprint $table) {
            $table->dropIndex(['tender_id', 'status']);
            $table->dropConstrainedForeignId('requested_by');
            $table->dropColumn(['status', 'decided_at', 'decision_reason']);
        });
    }
};
